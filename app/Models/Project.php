<?php

namespace App\Models;

use App\Services\GitStatusService;
use App\Services\TechnicalDebtSignalService;
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
        'name', 'slug', 'path', 'description', 'tech_stack', 'detected_files', 'git_info', 'size_bytes',
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
        'size_bytes' => 'integer',
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
     * Human-readable disk size (e.g. "1.5 MB"). Not Laravel's Number::fileSize()
     * — that requires the intl extension, which this container doesn't have.
     */
    public function getFormattedSizeAttribute(): ?string
    {
        if ($this->size_bytes === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) $this->size_bytes;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        $decimals = $unitIndex === 0 ? 0 : (fmod($bytes, 1) === 0.0 ? 0 : 1);

        return number_format($bytes, $decimals).' '.$units[$unitIndex];
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

        $basePath = config('services.scanner.base_path');

        return app(GitStatusService::class)->forPath($basePath.'/'.$this->path);
    }

    /**
     * Live count of TODO/FIXME markers in the project's source tree.
     *
     * @return array{todo: int, fixme: int, total: int}
     */
    public function liveTechnicalDebtSignal(): array
    {
        if (! $this->path || ! $this->git_info) {
            return ['todo' => 0, 'fixme' => 0, 'total' => 0];
        }

        $basePath = config('services.scanner.base_path');

        return app(TechnicalDebtSignalService::class)->forPath($basePath.'/'.$this->path);
    }
}
