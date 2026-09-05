<?php

namespace App\Models;

use App\Contracts\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalDebt extends Model implements Searchable
{
    protected $fillable = ['project_id', 'title', 'resolved', 'resolved_at', 'order'];

    protected $casts = [
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function searchableType(): string
    {
        return 'technical_debt';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function searchableContent(): array
    {
        return [$this->title, ''];
    }
}
