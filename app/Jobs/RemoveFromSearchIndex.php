<?php

namespace App\Jobs;

use App\Services\EmbeddingIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveFromSearchIndex implements ShouldQueue
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
        $index->removePoint($this->type, $this->id);
    }
}
