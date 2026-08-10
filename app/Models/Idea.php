<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Idea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'content', 'status_id', 'priority_id', 'converted_to_project_id'];

    protected static function booted(): void
    {
        static::creating(function (Idea $idea) {
            if (empty($idea->status_id)) {
                $idea->status_id = IdeaStatus::where('code', 'raw')->value('id');
            }
            if (empty($idea->priority_id)) {
                $idea->priority_id = IdeaPriority::where('code', 'medium')->value('id');
            }
        });
    }

    public function annotations(): MorphMany
    {
        return $this->morphMany(Annotation::class, 'annotatable')->orderByDesc('pinned')->orderByDesc('created_at');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(IdeaStatus::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(IdeaPriority::class);
    }

    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_to_project_id');
    }
}
