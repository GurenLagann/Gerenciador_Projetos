<?php

namespace App\Mcp\Tools;

use App\Models\Idea;
use App\Services\IdeaConversionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Convert an idea into a new project.')]
class ConvertIdeaTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate(['id' => 'required|integer']);

        $idea = Idea::find($request->get('id'));

        if (! $idea) {
            return Response::error("No idea found with id [{$request->get('id')}].");
        }

        $project = app(IdeaConversionService::class)->convert($idea);

        return Response::structured(['project_slug' => $project->slug, 'project_id' => $project->id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The idea ID to convert.')->required(),
        ];
    }
}
