<?php

namespace App\Models;

use App\Services\GitStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'path', 'description', 'tech_stack', 'detected_files', 'git_info',
        'runtime_version', 'framework_version', 'database_engine',
        'status_id', 'progress', 'color', 'icon', 'is_scanned', 'last_scanned_at',
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
            if (empty($project->status_id)) {
                $project->status_id = ProjectStatus::where('code', 'planning')->value('id');
            }
        });
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('order');
    }

    public function technicalDebts(): HasMany
    {
        return $this->hasMany(TechnicalDebt::class)->orderBy('order');
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
        return $this->belongsTo(ProjectStatus::class);
    }

    public function getMilestoneProgressAttribute(): int
    {
        $total = $this->milestones()->count();
        if ($total === 0) {
            return 0;
        }
        $completed = $this->milestones()->where('completed', true)->count();

        return (int) round(($completed / $total) * 100);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Live (per-request) git status: uncommitted changes and ahead/behind vs.
     * the upstream branch. Unlike `git_info` (a snapshot written by the
     * scanner), this runs git commands against the repo on every call.
     *
     * @return array{dirty: bool|null, has_upstream: bool, ahead: int|null, behind: int|null}
     */
    public function liveGitStatus(): array
    {
        if (! $this->path || ! $this->git_info) {
            return ['dirty' => null, 'has_upstream' => false, 'ahead' => null, 'behind' => null];
        }

        $basePath = env('SCAN_BASE_PATH', '/var/www/host_projects');

        return app(GitStatusService::class)->forPath($basePath.'/'.$this->path);
    }
}
