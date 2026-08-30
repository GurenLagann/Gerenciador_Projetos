<?php

namespace App\Mcp\Tools;

use App\Models\Milestone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Toggle a milestone between completed and not completed.')]
class ToggleMilestoneTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate(['id' => 'required|integer']);

        $milestone = Milestone::find($request->get('id'));

        if (! $milestone) {
            return Response::error("No milestone found with id [{$request->get('id')}].");
        }

        $milestone->update([
            'completed' => ! $milestone->completed,
            'completed_at' => ! $milestone->completed ? now() : null,
        ]);

        return Response::structured(['id' => $milestone->id, 'completed' => $milestone->completed]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The milestone ID.')->required(),
        ];
    }
}
