<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Annotation extends Model
{
    protected $fillable = ['title', 'content', 'pinned', 'color', 'annotatable_type', 'annotatable_id'];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    public function annotatable(): MorphTo
    {
        return $this->morphTo();
    }
}
