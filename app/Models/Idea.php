<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Idea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'content', 'status', 'priority', 'converted_to_project_id'];

    public function annotations(): MorphMany
    {
        return $this->morphMany(Annotation::class, 'annotatable')->orderByDesc('pinned')->orderByDesc('created_at');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_to_project_id');
    }
}
