<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a technical debt item to a project.')]
class CreateTechnicalDebtTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_slug' => 'required|string',
            'title' => 'required|string|max:255',
        ]);

        $project = Project::where('slug', $validated['project_slug'])->first();

        if (! $project) {
            return Response::error("No project found with slug [{$validated['project_slug']}].");
        }

        $order = $project->technicalDebts()->max('order') + 1;

        $debt = $project->technicalDebts()->create([
            'title' => $validated['title'],
            'order' => $order,
        ]);

        return Response::structured(['id' => $debt->id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_slug' => $schema->string()->description('The project slug.')->required(),
            'title' => $schema->string()->description('Technical debt item title.')->required(),
        ];
    }
}
