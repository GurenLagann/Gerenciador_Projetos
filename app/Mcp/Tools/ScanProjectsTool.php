<?php

namespace App\Mcp\Tools;

use App\Services\ProjectScannerService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Scan the host filesystem for git repositories and import them as projects.')]
class ScanProjectsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $dryRun = (bool) $request->get('dry_run', false);

        $results = app(ProjectScannerService::class)->scan($dryRun);

        return Response::structured(['dry_run' => $dryRun, 'projects' => $results]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'dry_run' => $schema->boolean()->description('If true, list what would be imported without saving.'),
        ];
    }
}
