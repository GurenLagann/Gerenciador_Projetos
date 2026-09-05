<?php

namespace Tests\Feature\Models;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectLiveGitStatusTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/project_live_git_status_test_'.uniqid();
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

    protected function git(string $cmd, ?string $repoDir = 'repo'): string
    {
        return trim(shell_exec('git -C '.escapeshellarg($this->basePath.'/'.$repoDir)." $cmd 2>&1") ?? '');
    }

    public function test_it_skips_the_git_call_and_returns_nulls_for_a_project_without_git_info(): void
    {
        $project = Project::factory()->create(['path' => 'not-a-real-repo', 'git_info' => null]);

        $status = $project->liveGitStatus();

        $this->assertSame(
            ['dirty' => null, 'has_upstream' => false, 'ahead' => null, 'behind' => null],
            $status
        );
    }

    public function test_it_reports_dirty_true_for_a_project_with_uncommitted_changes(): void
    {
        mkdir($this->basePath.'/repo', 0777, true);
        $this->git('init -q -b main');
        $this->git('config user.email test@example.com');
        $this->git('config user.name "Test User"');
        file_put_contents($this->basePath.'/repo/file.txt', "hello\n");
        $this->git('add file.txt');
        $this->git('commit -q -m "initial commit"');

        file_put_contents($this->basePath.'/repo/file.txt', "changed\n");

        $project = Project::factory()->create(['path' => 'repo', 'git_info' => ['branch' => 'main']]);

        $status = $project->liveGitStatus();

        $this->assertTrue($status['dirty']);
    }

    protected function makeCleanRepoProject(string $dir = 'repo'): Project
    {
        mkdir($this->basePath.'/'.$dir, 0777, true);
        $this->git('init -q -b main', $dir);
        $this->git('config user.email test@example.com', $dir);
        $this->git('config user.name "Test User"', $dir);
        file_put_contents($this->basePath.'/'.$dir.'/file.txt', "hello\n");
        $this->git('add file.txt', $dir);
        $this->git('commit -q -m "initial commit"', $dir);

        return Project::factory()->create(['path' => $dir, 'git_info' => ['branch' => 'main']]);
    }

    public function test_it_caches_the_result_for_a_short_time_instead_of_shelling_out_on_every_call(): void
    {
        $project = $this->makeCleanRepoProject();
        $this->assertFalse($project->liveGitStatus()['dirty']);

        // Debt #25: _card.blade.php calls this for every project on the
        // listing page, on every request, with no cache — up to 2 shell_exec
        // per card. Dirtying the tree after the first call and expecting the
        // stale (clean) result back proves the second call didn't shell out.
        file_put_contents($this->basePath.'/repo/file.txt', "changed\n");

        $this->assertFalse($project->liveGitStatus()['dirty']);
    }

    public function test_it_refreshes_the_status_once_the_short_cache_expires(): void
    {
        $project = $this->makeCleanRepoProject();
        $this->assertFalse($project->liveGitStatus()['dirty']);

        file_put_contents($this->basePath.'/repo/file.txt', "changed\n");
        $this->travel(31)->seconds();

        $this->assertTrue($project->liveGitStatus()['dirty']);
    }

    public function test_it_caches_independently_per_project(): void
    {
        $projectA = $this->makeCleanRepoProject('repo-a');
        $projectB = $this->makeCleanRepoProject('repo-b');
        file_put_contents($this->basePath.'/repo-b/file.txt', "changed\n");

        $this->assertFalse($projectA->liveGitStatus()['dirty']);
        $this->assertTrue($projectB->liveGitStatus()['dirty']);
    }
}
