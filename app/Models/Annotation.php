<?php

namespace App\Models;

use App\Contracts\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Annotation extends Model implements Searchable
{
    protected $fillable = ['title', 'content', 'pinned', 'color', 'annotatable_type', 'annotatable_id'];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    public function annotatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function searchableType(): string
    {
        return 'annotation';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function searchableContent(): array
    {
        $title = $this->title ?: ($this->annotatable->name ?? $this->annotatable->title ?? 'Annotation');

        return [$title, (string) $this->content];
    }
}
