<?php

namespace App\Jobs;

use App\Services\EmbeddingIndexService;
use App\Services\FailedIndexingDebtRecorder;
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

    public function failed(\Throwable $exception): void
    {
        app(FailedIndexingDebtRecorder::class)->record(
            "remove:{$this->type}:{$this->id}",
            $exception->getMessage(),
        );
    }
}
