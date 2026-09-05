<?php

namespace App\Models;

use App\Contracts\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model implements Searchable
{
    protected $fillable = ['project_id', 'title', 'description', 'completed', 'due_date', 'completed_at', 'order'];

    protected $casts = [
        'completed' => 'boolean',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function searchableType(): string
    {
        return 'milestone';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function searchableContent(): array
    {
        return [$this->title, (string) $this->description];
    }
}
