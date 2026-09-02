<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Services\ProjectScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectScannerServiceEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/scanner_enrichment_test_'.uniqid();
        mkdir($this->basePath, 0777, true);
        $this->overrideScanBasePath($this->basePath);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);
        $this->overrideScanBasePath('/var/www/host_projects');

        parent::tearDown();
    }

    protected function overrideScanBasePath(string $path): void
    {
        config(['services.scanner.base_path' => $path]);
    }

    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    protected function git(string $repoPath, string $cmd): string
    {
        return trim(shell_exec('git -C '.escapeshellarg($repoPath)." $cmd 2>&1") ?? '');
    }

    public function test_scan_captures_unique_contributors_and_project_size_in_bytes(): void
    {
        $repoPath = $this->basePath.'/repo';
        mkdir($repoPath, 0777, true);

        $this->git($repoPath, 'init -q -b main');

        $this->git($repoPath, 'config user.email alice@example.com');
        $this->git($repoPath, 'config user.name Alice');
        file_put_contents($repoPath.'/composer.json', str_repeat('a', 100));
        $this->git($repoPath, 'add composer.json');
        $this->git($repoPath, 'commit -q -m "initial commit"');

        $this->git($repoPath, 'config user.email bob@example.com');
        $this->git($repoPath, 'config user.name Bob');
        file_put_contents($repoPath.'/README.md', str_repeat('b', 50));
        $this->git($repoPath, 'add README.md');
        $this->git($repoPath, 'commit -q -m "add readme"');

        // Dependency noise that must not count toward size.
        mkdir($repoPath.'/vendor', 0777, true);
        file_put_contents($repoPath.'/vendor/lib.php', str_repeat('c', 5000));

        (new ProjectScannerService)->scan();

        $project = Project::where('path', 'repo')->firstOrFail();

        $this->assertEqualsCanonicalizing(['Alice', 'Bob'], $project->git_info['contributors']);
        $this->assertSame(150, $project->size_bytes);
    }

    public function test_scan_scopes_git_history_to_each_service_inside_a_monorepo(): void
    {
        $repoPath = $this->basePath.'/microservices';
        mkdir($repoPath.'/api', 0777, true);
        mkdir($repoPath.'/sith', 0777, true);

        $this->git($repoPath, 'init -q -b main');

        $this->git($repoPath, 'config user.email ana@example.com');
        $this->git($repoPath, 'config user.name Ana');
        file_put_contents($repoPath.'/api/composer.json', '{}');
        $this->git($repoPath, 'add api');
        $this->git($repoPath, 'commit -q -m "api: bootstrap"');

        $this->git($repoPath, 'config user.email bruno@example.com');
        $this->git($repoPath, 'config user.name Bruno');
        file_put_contents($repoPath.'/sith/composer.json', '{}');
        $this->git($repoPath, 'add sith');
        $this->git($repoPath, 'commit -q -m "sith: bootstrap"');

        (new ProjectScannerService)->scan();

        $api = Project::where('path', 'microservices/api')->firstOrFail();
        $sith = Project::where('path', 'microservices/sith')->firstOrFail();

        $this->assertSame(['Ana'], $api->git_info['contributors']);
        $this->assertSame(['Bruno'], $sith->git_info['contributors']);
        $this->assertSame(1, $api->git_info['total_commits']);
        $this->assertSame(1, $sith->git_info['total_commits']);
        $this->assertSame('main', $api->git_info['branch']);
    }
}
