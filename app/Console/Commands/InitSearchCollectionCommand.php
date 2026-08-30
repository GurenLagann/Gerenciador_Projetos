<?php

namespace App\Console\Commands;

use App\Services\EmbeddingIndexService;
use Illuminate\Console\Command;

class InitSearchCollectionCommand extends Command
{
    protected $signature = 'search:init-collection';

    protected $description = 'Create the Qdrant collection used for semantic search (idempotent)';

    public function handle(EmbeddingIndexService $index): int
    {
        $index->ensureCollection();

        $this->info('Qdrant collection is ready.');

        return Command::SUCCESS;
    }
}
