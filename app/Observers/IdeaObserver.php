<?php

namespace App\Observers;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Idea;

class IdeaObserver
{
    public function saved(Idea $idea): void
    {
        IndexSearchableContent::dispatch('idea', $idea->id);
    }

    public function deleted(Idea $idea): void
    {
        RemoveFromSearchIndex::dispatch('idea', $idea->id);
    }
}
