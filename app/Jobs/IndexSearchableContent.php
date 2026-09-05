<?php

namespace App\Jobs;

use App\Contracts\Searchable;
use App\Services\EmbeddingIndexService;
use App\Services\FailedIndexingDebtRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexSearchableContent implements ShouldQueue
{
    use Queueable;

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model&Searchable>  $modelClass
     */
    public function __construct(
        public string $modelClass,
        public int $id,
    ) {
        //
    }

    public function handle(EmbeddingIndexService $index): void
    {
        $model = $this->modelClass::find($this->id);

        if (! $model instanceof Searchable) {
            return;
        }

        [$title, $text] = $model->searchableContent();

        if ($title === null) {
            return;
        }

        $index->upsertPoint($model->searchableType(), $this->id, $title, $text);
    }

    public function failed(\Throwable $exception): void
    {
        app(FailedIndexingDebtRecorder::class)->record(
            "index:{$this->modelClass}:{$this->id}",
            $exception->getMessage(),
        );
    }
}
