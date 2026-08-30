<?php

namespace App\Observers;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Project;

class ProjectObserver
{
    public function saved(Project $project): void
    {
        IndexSearchableContent::dispatch('project', $project->id);
    }

    public function deleted(Project $project): void
    {
        RemoveFromSearchIndex::dispatch('project', $project->id);
    }
}
