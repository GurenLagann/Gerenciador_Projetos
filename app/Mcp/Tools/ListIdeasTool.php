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

#[Description('List ideas on the kanban board, optionally filtered by status.')]
#[IsReadOnly]
class ListIdeasTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $query = Idea::with(['tags', 'status', 'priority']);

        if ($status = $request->get('status')) {
            $query->whereRelation('status', 'code', $status);
        }

        $ideas = $query->limit((int) $request->get('limit', 25))->get();

        return Response::structured([
            'ideas' => $ideas->map(fn (Idea $idea) => [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
                'status' => $idea->status->code,
                'priority' => $idea->priority->code,
                'tags' => $idea->tags->pluck('name')->all(),
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('Filter by idea status code (e.g. raw, in-progress, converted).'),
            'limit' => $schema->integer()
                ->description('Maximum number of ideas to return. Defaults to 25.'),
        ];
    }
}
