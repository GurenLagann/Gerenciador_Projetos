<?php

namespace App\Mcp\Tools;

use App\Models\Idea;
use App\Support\ResolvesIdeaStatusAndPriority;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new idea on the kanban board.')]
class CreateIdeaTool extends Tool
{
    use ResolvesIdeaStatusAndPriority;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'status' => ['nullable', Rule::exists('idea_statuses', 'code')],
            'priority' => ['nullable', Rule::exists('idea_priorities', 'code')],
        ]);

        $idea = Idea::create($this->resolveStatusAndPriority($validated));

        return Response::structured(['id' => $idea->id]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Idea title.')->required(),
            'description' => $schema->string()->description('Short description.'),
            'content' => $schema->string()->description('Longer free-form content.'),
            'status' => $schema->string()->description('Idea status code (e.g. raw, in-progress). Defaults to raw.'),
            'priority' => $schema->string()->description('Idea priority code (e.g. low, medium, high). Defaults to medium.'),
        ];
    }
}
