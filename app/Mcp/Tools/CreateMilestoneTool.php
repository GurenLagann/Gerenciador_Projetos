<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a milestone to a project.')]
class CreateMilestoneTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_slug' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $project = Project::where('slug', $validated['project_slug'])->first();

        if (! $project) {
            return Response::error("No project found with slug [{$validated['project_slug']}].");
        }

        $order = $project->milestones()->max('order') + 1;

        $milestone = $project->milestones()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'order' => $order,
        ]);

        return Response::structured(['id' => $milestone->id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_slug' => $schema->string()->description('The project slug.')->required(),
            'title' => $schema->string()->description('Milestone title.')->required(),
            'description' => $schema->string()->description('Optional description.'),
            'due_date' => $schema->string()->description('Optional due date (YYYY-MM-DD).'),
        ];
    }
}
