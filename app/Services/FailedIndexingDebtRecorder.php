<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Str;

class FailedIndexingDebtRecorder
{
    /**
     * Create a technical debt entry the first time a given error message
     * is seen from a permanently-failed indexing job. Dedup is keyed on
     * the message, not the specific job/record ($key is kept in the title
     * only for context) — this matters because TechnicalDebt is itself
     * Searchable: recording a debt queues an index job for it, and if the
     * backend is still down that job fails too, calling record() again
     * with a *different* $key (the new debt's own id). Deduping by message
     * collapses that whole cascade onto one open debt instead of minting
     * a new one per iteration. The `resolved = false` check means a
     * recurrence of a problem the user already resolved is reported again,
     * rather than silently suppressed forever.
     */
    public function record(string $key, string $message): void
    {
        $project = Project::where('slug', 'gerenciador-projetos')->first();

        if (! $project) {
            return;
        }

        $signature = Str::limit($message, 150);
        $prefix = "Job de indexação RAG falhou: {$signature}";

        // Escape LIKE wildcards ('%', '_') and the escape character itself
        // that may appear in $signature, so a message containing one of
        // these doesn't accidentally match an unrelated title. SQLite has
        // no default LIKE escape character, so it must be set explicitly.
        $escapedPrefix = addcslashes($prefix, '\%_');

        $alreadyOpen = $project->technicalDebts()
            ->where('resolved', false)
            ->whereRaw('title LIKE ? ESCAPE ?', ["{$escapedPrefix}%", '\\'])
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        $project->technicalDebts()->create([
            'title' => "{$prefix} (ex.: {$key})",
        ]);
    }
}
