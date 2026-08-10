<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStatus extends Model
{
    protected $fillable = ['code', 'label', 'color', 'bg', 'bar', 'sort_order'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
