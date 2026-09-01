<?php

namespace App\Observers;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Milestone;

class MilestoneObserver
{
    public function saved(Milestone $milestone): void
    {
        IndexSearchableContent::dispatch('milestone', $milestone->id);
    }

    public function deleted(Milestone $milestone): void
    {
        RemoveFromSearchIndex::dispatch('milestone', $milestone->id);
    }
}
