<?php

namespace App\Console\Commands;

use App\Services\EmbeddingIndexService;
use Illuminate\Console\Command;

class ReconcileSearchIndexCommand extends Command
{
    protected $signature = 'search:reconcile-index';

    protected $description = 'Diff the Qdrant search index against the database and queue jobs to close any gap (missing or orphaned points)';

    public function handle(EmbeddingIndexService $index): int
    {
        $this->info('Comparing the search index against the database...');

        $summary = $index->reconcile();

        $this->table(
            ['Type', 'Missing (queued to index)', 'Orphaned (queued to remove)'],
            collect($summary)->map(fn ($counts, $type) => [$type, $counts['missing'], $counts['orphaned']])->all()
        );

        $this->info('Jobs queued. Run a queue worker (php artisan queue:work) to process them.');

        return Command::SUCCESS;
    }
}
