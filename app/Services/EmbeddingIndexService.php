<?php

namespace App\Services;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\TechnicalDebt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmbeddingIndexService
{
    protected string $ollamaBaseUrl;

    protected string $embedModel;

    protected string $qdrantBaseUrl;

    protected string $collection;

    public function __construct()
    {
        $this->ollamaBaseUrl = config('services.ollama.base_url');
        $this->embedModel = config('services.ollama.embed_model');
        $this->qdrantBaseUrl = config('services.qdrant.base_url');
        $this->collection = config('services.qdrant.collection');
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $response = Http::baseUrl($this->ollamaBaseUrl)
            ->timeout(30)
            ->post('/api/embed', [
                'model' => $this->embedModel,
                'input' => $text,
            ])
            ->throw();

        return $response->json('embeddings.0');
    }

    public function ensureCollection(): void
    {
        $exists = Http::baseUrl($this->qdrantBaseUrl)
            ->get("/collections/{$this->collection}")
            ->successful();

        if ($exists) {
            return;
        }

        Http::baseUrl($this->qdrantBaseUrl)
            ->put("/collections/{$this->collection}", [
                'vectors' => ['size' => 1024, 'distance' => 'Cosine'],
            ])
            ->throw();
    }

    public function upsertPoint(string $type, int $id, string $title, string $text): void
    {
        if (trim($title.$text) === '') {
            $this->removePoint($type, $id);

            return;
        }

        $vector = $this->embed(trim($title."\n\n".$text));

        Http::baseUrl($this->qdrantBaseUrl)
            ->put("/collections/{$this->collection}/points", [
                'points' => [[
                    'id' => $this->pointId($type, $id),
                    'vector' => $vector,
                    'payload' => [
                        'type' => $type,
                        'source_id' => $id,
                        'title' => $title,
                        'text' => $text,
                    ],
                ]],
            ])
            ->throw();
    }

    public function removePoint(string $type, int $id): void
    {
        Http::baseUrl($this->qdrantBaseUrl)
            ->post("/collections/{$this->collection}/points/delete", [
                'points' => [$this->pointId($type, $id)],
            ])
            ->throw();
    }

    /**
     * @param  array<int, string>|null  $types
     * @return array<int, array{type: string, source_id: int, title: string, snippet: string, score: float}>
     */
    public function search(string $query, ?array $types = null, int $limit = 10): array
    {
        $vector = $this->embed($query);

        $body = [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
        ];

        if ($types !== null && $types !== []) {
            $body['filter'] = [
                'must' => [
                    ['key' => 'type', 'match' => ['any' => $types]],
                ],
            ];
        }

        $response = Http::baseUrl($this->qdrantBaseUrl)
            ->post("/collections/{$this->collection}/points/search", $body)
            ->throw();

        return collect($response->json('result', []))
            ->map(fn (array $point) => [
                'type' => $point['payload']['type'],
                'source_id' => $point['payload']['source_id'],
                'title' => $point['payload']['title'],
                'snippet' => Str::limit($point['payload']['text'], 240),
                'score' => $point['score'],
            ])
            ->all();
    }

    /**
     * All `source_id`s currently indexed under $type in Qdrant, paginating
     * through the full result set via `next_page_offset`.
     *
     * @return array<int, int>
     */
    public function indexedSourceIds(string $type): array
    {
        $ids = [];
        $offset = null;

        do {
            $body = [
                'limit' => 250,
                'with_payload' => true,
                'with_vector' => false,
                'filter' => [
                    'must' => [
                        ['key' => 'type', 'match' => ['value' => $type]],
                    ],
                ],
            ];

            if ($offset !== null) {
                $body['offset'] = $offset;
            }

            $response = Http::baseUrl($this->qdrantBaseUrl)
                ->post("/collections/{$this->collection}/points/scroll", $body)
                ->throw();

            foreach ($response->json('result.points', []) as $point) {
                $ids[] = (int) $point['payload']['source_id'];
            }

            $offset = $response->json('result.next_page_offset');
        } while ($offset !== null);

        return $ids;
    }

    /**
     * Diffs the database (source of truth) against the Qdrant index per
     * Searchable type and dispatches the jobs needed to close the gap:
     * missing records get (re)indexed, points with no live record behind
     * them get removed. `annotation`, `milestone` and `technical_debt` all
     * depend on a Project/Idea parent that can be soft-deleted — a record
     * whose parent is gone counts as "should not be indexed", not as
     * missing. Safe to run repeatedly; see
     * docs/superpowers/specs/2026-09-05-rag-hardening-design.md.
     *
     * @return array<string, array{missing: int, orphaned: int}>
     */
    public function reconcile(): array
    {
        $this->ensureCollection();

        $expectedIds = [
            'project' => Project::query()->pluck('id')->all(),
            'idea' => Idea::query()->pluck('id')->all(),
            'annotation' => Annotation::whereHasMorph('annotatable', [Project::class, Idea::class])->pluck('id')->all(),
            'milestone' => Milestone::whereHas('project')->pluck('id')->all(),
            'technical_debt' => TechnicalDebt::whereHas('project')->pluck('id')->all(),
        ];

        $modelClasses = [
            'project' => Project::class,
            'idea' => Idea::class,
            'annotation' => Annotation::class,
            'milestone' => Milestone::class,
            'technical_debt' => TechnicalDebt::class,
        ];

        $summary = [];

        foreach ($expectedIds as $type => $ids) {
            $indexed = $this->indexedSourceIds($type);

            $missing = array_diff($ids, $indexed);
            $orphaned = array_diff($indexed, $ids);

            foreach ($missing as $id) {
                IndexSearchableContent::dispatch($modelClasses[$type], $id);
            }

            foreach ($orphaned as $id) {
                RemoveFromSearchIndex::dispatch($type, $id);
            }

            $summary[$type] = ['missing' => count($missing), 'orphaned' => count($orphaned)];
        }

        return $summary;
    }

    /**
     * Dispatch an indexing job for every existing Project/Idea/Annotation.
     * Used to backfill the index; observers handle future writes.
     *
     * @return array<string, int>
     */
    public function rebuildAll(): array
    {
        $this->ensureCollection();

        $counts = ['project' => 0, 'idea' => 0, 'annotation' => 0, 'milestone' => 0, 'technical_debt' => 0];

        Project::query()->select('id')->each(function (Project $project) use (&$counts) {
            IndexSearchableContent::dispatch(Project::class, $project->id);
            $counts['project']++;
        });

        Idea::query()->select('id')->each(function (Idea $idea) use (&$counts) {
            IndexSearchableContent::dispatch(Idea::class, $idea->id);
            $counts['idea']++;
        });

        Annotation::query()->select('id')->each(function (Annotation $annotation) use (&$counts) {
            IndexSearchableContent::dispatch(Annotation::class, $annotation->id);
            $counts['annotation']++;
        });

        Milestone::query()->select('id')->each(function (Milestone $milestone) use (&$counts) {
            IndexSearchableContent::dispatch(Milestone::class, $milestone->id);
            $counts['milestone']++;
        });

        TechnicalDebt::query()->select('id')->each(function (TechnicalDebt $debt) use (&$counts) {
            IndexSearchableContent::dispatch(TechnicalDebt::class, $debt->id);
            $counts['technical_debt']++;
        });

        return $counts;
    }

    /**
     * Deterministic integer point ID so re-indexing a record overwrites
     * its existing point instead of creating a duplicate. Offsetting by
     * type avoids collisions between the three source tables' own
     * auto-increment ID spaces.
     */
    protected function pointId(string $type, int $id): int
    {
        $offset = match ($type) {
            'project' => 1_000_000_000,
            'idea' => 2_000_000_000,
            'annotation' => 3_000_000_000,
            'milestone' => 4_000_000_000,
            'technical_debt' => 5_000_000_000,
        };

        return $offset + $id;
    }
}
