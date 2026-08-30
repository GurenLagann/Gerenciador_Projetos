<?php

namespace App\Mcp\Tools;

use App\Models\Annotation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Toggle the pinned state of an annotation.')]
class PinAnnotationTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate(['id' => 'required|integer']);

        $annotation = Annotation::find($request->get('id'));

        if (! $annotation) {
            return Response::error("No annotation found with id [{$request->get('id')}].");
        }

        $annotation->update(['pinned' => ! $annotation->pinned]);

        return Response::structured(['id' => $annotation->id, 'pinned' => $annotation->pinned]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The annotation ID.')->required(),
        ];
    }
}
