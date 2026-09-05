<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Str;

class FailedIndexingDebtRecorder
{
    /**
     * Create a technical debt entry the first time a given indexing job
     * (identified by $key, e.g. "index:App\Models\Annotation:44") fails
     * permanently. Later failures of the same job are silent no-ops so a
     * persistent problem doesn't flood the board with duplicates.
     */
    public function record(string $key, string $message): void
    {
        $project = Project::where('slug', 'gerenciador-projetos')->first();

        if (! $project) {
            return;
        }

        $prefix = "Job de indexação RAG falhou ({$key})";

        if ($project->technicalDebts()->where('title', 'like', "{$prefix}%")->exists()) {
            return;
        }

        $project->technicalDebts()->create([
            'title' => "{$prefix}: ".Str::limit($message, 150),
        ]);
    }
}
