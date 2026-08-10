<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdeaPriority extends Model
{
    protected $fillable = ['code', 'label', 'dot', 'sort_order'];

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }
}
