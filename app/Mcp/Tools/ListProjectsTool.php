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

#[Description('List projects tracked in the dashboard, optionally filtered by status and sorted.')]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $query = Project::withCount('milestones')->with('status');

        if ($status = $request->get('status')) {
            $query->whereRelation('status', 'code', $status);
        }

        match ($request->get('sort', 'latest')) {
            'name' => $query->orderBy('name'),
            'progress' => $query->orderByDesc('progress'),
            'status' => $query->join('project_statuses', 'project_statuses.id', '=', 'projects.status_id')
                ->orderBy('project_statuses.sort_order')
                ->select('projects.*'),
            default => $query->latest(),
        };

        $projects = $query->limit((int) $request->get('limit', 25))->get();

        return Response::structured([
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status->code,
                'progress' => $project->progress,
                'milestones_count' => $project->milestones_count,
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('Filter by project status code (e.g. planning, in-progress, done).'),
            'sort' => $schema->string()
                ->enum(['latest', 'name', 'progress', 'status'])
                ->description('Sort order. Defaults to latest.'),
            'limit' => $schema->integer()
                ->description('Maximum number of projects to return. Defaults to 25.'),
        ];
    }
}
