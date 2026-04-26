<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'path', 'description', 'tech_stack', 'detected_files', 'git_info',
        'status', 'progress', 'color', 'icon', 'is_scanned', 'last_scanned_at',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'detected_files' => 'array',
        'git_info' => 'array',
        'is_scanned' => 'boolean',
        'last_scanned_at' => 'datetime',
        'progress' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('order');
    }

    public function annotations(): MorphMany
    {
        return $this->morphMany(Annotation::class, 'annotatable')->orderByDesc('pinned')->orderByDesc('created_at');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    public function getMilestoneProgressAttribute(): int
    {
        $total = $this->milestones()->count();
        if ($total === 0) return 0;
        $completed = $this->milestones()->where('completed', true)->count();
        return (int) round(($completed / $total) * 100);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
