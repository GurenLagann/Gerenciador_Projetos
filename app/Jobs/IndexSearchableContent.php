<?php

namespace App\Jobs;

use App\Models\Annotation;
use App\Models\Idea;
use App\Models\Project;
use App\Services\EmbeddingIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexSearchableContent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $type,
        public int $id,
    ) {
        //
    }

    public function handle(EmbeddingIndexService $index): void
    {
        [$title, $text] = match ($this->type) {
            'project' => $this->projectContent(),
            'idea' => $this->ideaContent(),
            'annotation' => $this->annotationContent(),
            default => [null, null],
        };

        if ($title === null) {
            return;
        }

        $index->upsertPoint($this->type, $this->id, $title, $text);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function projectContent(): array
    {
        $project = Project::find($this->id);

        return $project ? [$project->name, (string) $project->description] : [null, null];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function ideaContent(): array
    {
        $idea = Idea::find($this->id);

        return $idea ? [$idea->title, trim($idea->description."\n\n".$idea->content)] : [null, null];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function annotationContent(): array
    {
        $annotation = Annotation::find($this->id);

        if (! $annotation) {
            return [null, null];
        }

        $title = $annotation->title ?: ($annotation->annotatable->name ?? $annotation->annotatable->title ?? 'Annotation');

        return [$title, (string) $annotation->content];
    }
}
