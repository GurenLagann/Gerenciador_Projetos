<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get full details for a single project by its slug, including milestones, annotations, and tags.')]
#[IsReadOnly]
class GetProjectTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate(['slug' => 'required|string']);

        $project = Project::with(['milestones', 'annotations', 'tags', 'status'])
            ->where('slug', $request->get('slug'))
            ->first();

        if (! $project) {
            return Response::error("No project found with slug [{$request->get('slug')}].");
        }

        return Response::structured([
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
            'description' => $project->description,
            'path' => $project->path,
            'status' => $project->status->code,
            'progress' => $project->progress,
            'tech_stack' => $project->tech_stack,
            'tags' => $project->tags->pluck('name')->all(),
            'milestones' => $project->milestones->map(fn ($m) => [
                'title' => $m->title,
                'completed' => $m->completed,
                'due_date' => $m->due_date?->toDateString(),
            ])->all(),
            'annotations' => $project->annotations->map(fn ($a) => [
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
            'slug' => $schema->string()->description('The project slug (route key).')->required(),
        ];
    }
}
