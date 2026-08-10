<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdeaStatus extends Model
{
    protected $fillable = ['code', 'label', 'color', 'bg', 'border', 'header_bg', 'is_board_column', 'sort_order'];

    protected $casts = [
        'is_board_column' => 'boolean',
    ];

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }
}
