<?php

namespace App\Observers;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\TechnicalDebt;

class TechnicalDebtObserver
{
    public function saved(TechnicalDebt $technicalDebt): void
    {
        IndexSearchableContent::dispatch('technical_debt', $technicalDebt->id);
    }

    public function deleted(TechnicalDebt $technicalDebt): void
    {
        RemoveFromSearchIndex::dispatch('technical_debt', $technicalDebt->id);
    }
}
