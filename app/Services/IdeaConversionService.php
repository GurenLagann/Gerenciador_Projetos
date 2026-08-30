<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Support\Str;

class IdeaConversionService
{
    public function convert(Idea $idea): Project
    {
        $slug = Str::slug($idea->title);
        $originalSlug = $slug;
        $i = 1;
        while (Project::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$i++;
        }

        $project = Project::create([
            'name' => $idea->title,
            'slug' => $slug,
            'path' => 'ideas/'.$slug,
            'description' => $idea->description,
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $idea->update([
            'status_id' => IdeaStatus::where('code', 'converted')->value('id'),
            'converted_to_project_id' => $project->id,
        ]);

        return $project;
    }
}
