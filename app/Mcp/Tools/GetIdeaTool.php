<?php

namespace App\Mcp\Tools;

use App\Models\Idea;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get full details for a single idea by its ID, including content, annotations, and tags.')]
#[IsReadOnly]
class GetIdeaTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate(['id' => 'required|integer']);

        $idea = Idea::with(['annotations', 'tags', 'status', 'priority'])->find($request->get('id'));

        if (! $idea) {
            return Response::error("No idea found with id [{$request->get('id')}].");
        }

        return Response::structured([
            'id' => $idea->id,
            'title' => $idea->title,
            'description' => $idea->description,
            'content' => $idea->content,
            'status' => $idea->status->code,
            'priority' => $idea->priority->code,
            'converted_to_project_id' => $idea->converted_to_project_id,
            'tags' => $idea->tags->pluck('name')->all(),
            'annotations' => $idea->annotations->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'pinned' => $a->pinned,
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The idea ID.')->required(),
        ];
    }
}
