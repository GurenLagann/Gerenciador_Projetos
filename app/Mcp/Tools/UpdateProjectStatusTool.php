<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update a project\'s status by slug.')]
#[IsIdempotent]
class UpdateProjectStatusTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => 'required|string',
            'status' => ['required', Rule::exists('project_statuses', 'code')],
        ]);

        $project = Project::where('slug', $validated['slug'])->first();

        if (! $project) {
            return Response::error("No project found with slug [{$validated['slug']}].");
        }

        $project->update(['status_id' => ProjectStatus::where('code', $validated['status'])->value('id')]);

        return Response::structured(['slug' => $project->slug, 'status' => $validated['status']]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The project slug.')->required(),
            'status' => $schema->string()->description('The new status code (e.g. planning, in-progress, done).')->required(),
        ];
    }
}
