<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'path' => $this->faker->unique()->slug(),
            'tech_stack' => ['Unknown'],
            'status_id' => ProjectStatus::where('code', 'idea')->value('id'),
            'is_scanned' => false,
        ];
    }
}
