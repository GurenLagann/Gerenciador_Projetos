<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ConvertIdeaTool;
use App\Mcp\Tools\CreateAnnotationTool;
use App\Mcp\Tools\CreateIdeaTool;
use App\Mcp\Tools\CreateMilestoneTool;
use App\Mcp\Tools\CreateTechnicalDebtTool;
use App\Mcp\Tools\GetIdeaTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\ListIdeasTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTagsTool;
use App\Mcp\Tools\PinAnnotationTool;
use App\Mcp\Tools\ScanProjectsTool;
use App\Mcp\Tools\SearchNotesTool;
use App\Mcp\Tools\ToggleMilestoneTool;
use App\Mcp\Tools\UpdateProjectProgressTool;
use App\Mcp\Tools\UpdateProjectStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Project Manager Server')]
#[Version('0.0.1')]
#[Instructions('Query and manage the local project management dashboard: projects, ideas, milestones, annotations, and tags. Deletion is not supported by design — ask the user to delete via the web UI if needed.')]
class ProjectManagerServer extends Server
{
    protected array $tools = [
        // Read-only
        ListProjectsTool::class,
        GetProjectTool::class,
        ListIdeasTool::class,
        GetIdeaTool::class,
        ListTagsTool::class,

        // Semantic search (RAG)
        SearchNotesTool::class,

        // Write
        UpdateProjectStatusTool::class,
        UpdateProjectProgressTool::class,
        CreateAnnotationTool::class,
        PinAnnotationTool::class,
        CreateMilestoneTool::class,
        ToggleMilestoneTool::class,
        CreateTechnicalDebtTool::class,
        CreateIdeaTool::class,
        ConvertIdeaTool::class,
        ScanProjectsTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
