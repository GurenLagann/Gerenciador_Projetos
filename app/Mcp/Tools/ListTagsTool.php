<?php

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List all tags, shared across projects and ideas.')]
#[IsReadOnly]
class ListTagsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $tags = Tag::withCount(['projects', 'ideas'])->orderBy('name')->get();

        return Response::structured([
            'tags' => $tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
                'color' => $tag->color,
                'projects_count' => $tag->projects_count,
                'ideas_count' => $tag->ideas_count,
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
