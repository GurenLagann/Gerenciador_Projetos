<?php

namespace App\Console\Commands;

use App\Services\ProjectScannerService;
use Illuminate\Console\Command;

class ScanProjectsCommand extends Command
{
    protected $signature = 'projects:scan {--dry-run : List projects without saving}';

    protected $description = 'Scan the host projects directory and import projects';

    public function handle(ProjectScannerService $scanner): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? 'Dry run - no changes will be saved.' : 'Scanning projects...');

        $results = $scanner->scan($dryRun);

        if (isset($results['error'])) {
            $this->error($results['error']);

            return Command::FAILURE;
        }

        $this->table(['Name', 'Path', 'Stack', 'Status'], array_map(fn ($p) => [
            $p['name'],
            $p['path'],
            implode(', ', $p['tech_stack']),
            $p['status'],
        ], $results));

        $this->info(count($results).' projects '.($dryRun ? 'found' : 'imported').'.');

        return Command::SUCCESS;
    }
}
