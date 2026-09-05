<?php

namespace App\Observers;

use App\Contracts\Searchable;
use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use Illuminate\Database\Eloquent\Model;

/**
 * Single Observer for every Searchable model (debt #26: five near-identical
 * Observers each doing nothing but dispatching these two jobs). Registered
 * once per model in AppServiceProvider; the per-type behavior lives on the
 * model itself via the Searchable contract, not here or in the job.
 */
class SearchableObserver
{
    public function saved(Model&Searchable $model): void
    {
        IndexSearchableContent::dispatch($model::class, $model->getKey());
    }

    public function deleted(Model&Searchable $model): void
    {
        RemoveFromSearchIndex::dispatch($model->searchableType(), $model->getKey());
    }
}
