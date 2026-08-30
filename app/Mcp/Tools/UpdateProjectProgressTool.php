<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update a project\'s progress percentage by slug.')]
#[IsIdempotent]
class UpdateProjectProgressTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $project = Project::where('slug', $validated['slug'])->first();

        if (! $project) {
            return Response::error("No project found with slug [{$validated['slug']}].");
        }

        $project->update(['progress' => $validated['progress']]);

        return Response::structured(['slug' => $project->slug, 'progress' => $project->progress]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The project slug.')->required(),
            'progress' => $schema->integer()->description('Progress percentage, 0-100.')->required(),
        ];
    }
}
