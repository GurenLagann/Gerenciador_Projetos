<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'color'];

    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable', 'taggables');
    }

    public function ideas(): MorphToMany
    {
        return $this->morphedByMany(Idea::class, 'taggable', 'taggables');
    }
}
