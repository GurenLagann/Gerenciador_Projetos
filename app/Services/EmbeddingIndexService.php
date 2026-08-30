<?php

namespace App\Services;

use App\Jobs\IndexSearchableContent;
use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Project;
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
        if (trim($text) === '') {
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
     * Dispatch an indexing job for every existing Project/Idea/Annotation.
     * Used to backfill the index; observers handle future writes.
     *
     * @return array<string, int>
     */
    public function rebuildAll(): array
    {
        $this->ensureCollection();

        $counts = ['project' => 0, 'idea' => 0, 'annotation' => 0];

        Project::query()->select('id')->each(function (Project $project) use (&$counts) {
            IndexSearchableContent::dispatch('project', $project->id);
            $counts['project']++;
        });

        Idea::query()->select('id')->each(function (Idea $idea) use (&$counts) {
            IndexSearchableContent::dispatch('idea', $idea->id);
            $counts['idea']++;
        });

        Annotation::query()->select('id')->each(function (Annotation $annotation) use (&$counts) {
            IndexSearchableContent::dispatch('annotation', $annotation->id);
            $counts['annotation']++;
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
        };

        return $offset + $id;
    }
}
