<?php

namespace App\Mcp\Tools;

use App\Models\Idea;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a Markdown annotation/note to a project or idea.')]
class CreateAnnotationTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'annotatable_type' => 'required|in:project,idea',
            'annotatable_id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'color' => 'nullable|string|max:20',
        ]);

        $model = $validated['annotatable_type'] === 'project'
            ? Project::find($validated['annotatable_id'])
            : Idea::find($validated['annotatable_id']);

        if (! $model) {
            return Response::error("No {$validated['annotatable_type']} found with id [{$validated['annotatable_id']}].");
        }

        $annotation = $model->annotations()->create([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'color' => $validated['color'] ?? null,
        ]);

        return Response::structured(['id' => $annotation->id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'annotatable_type' => $schema->string()->enum(['project', 'idea'])->required(),
            'annotatable_id' => $schema->integer()->description('ID of the project or idea (project id, not slug).')->required(),
            'title' => $schema->string()->description('Optional title for the note.'),
            'content' => $schema->string()->description('Markdown content of the note.')->required(),
            'color' => $schema->string()->description('Optional color tag.'),
        ];
    }
}
