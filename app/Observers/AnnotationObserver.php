<?php

namespace App\Observers;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Annotation;

class AnnotationObserver
{
    public function saved(Annotation $annotation): void
    {
        IndexSearchableContent::dispatch('annotation', $annotation->id);
    }

    public function deleted(Annotation $annotation): void
    {
        RemoveFromSearchIndex::dispatch('annotation', $annotation->id);
    }
}
