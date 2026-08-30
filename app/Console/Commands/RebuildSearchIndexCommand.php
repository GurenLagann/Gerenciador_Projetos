<?php

namespace App\Console\Commands;

use App\Services\EmbeddingIndexService;
use Illuminate\Console\Command;

class RebuildSearchIndexCommand extends Command
{
    protected $signature = 'search:rebuild-index';

    protected $description = 'Queue re-indexing of all projects, ideas, and annotations for semantic search';

    public function handle(EmbeddingIndexService $index): int
    {
        $this->info('Ensuring Qdrant collection exists and queuing indexing jobs...');

        $counts = $index->rebuildAll();

        $this->table(['Type', 'Queued'], collect($counts)->map(fn ($count, $type) => [$type, $count])->all());

        $this->info('Jobs queued. Run a queue worker (php artisan queue:work) to process them.');

        return Command::SUCCESS;
    }
}
