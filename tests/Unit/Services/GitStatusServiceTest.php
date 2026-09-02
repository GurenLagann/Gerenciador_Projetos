<?php

namespace Tests\Unit\Services;

use App\Services\GitStatusService;
use Tests\TestCase;

class GitStatusServiceTest extends TestCase
{
    protected string $repoPath;

    protected GitStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoPath = sys_get_temp_dir().'/git_status_test_'.uniqid();
        mkdir($this->repoPath, 0777, true);

        $this->service = new GitStatusService;
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->repoPath);
        $this->overrideScanBasePath('/var/www/host_projects');

        parent::tearDown();
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
            is_dir($path) && ! is_link($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    protected function git(string $cmd, ?string $cwd = null): string
    {
        return trim(shell_exec('git -C '.escapeshellarg($cwd ?? $this->repoPath)." $cmd 2>&1") ?? '');
    }

    protected function initRepoWithCommit(): void
    {
        $this->git('init -q -b main');
        $this->git('config user.email test@example.com');
        $this->git('config user.name "Test User"');
        file_put_contents($this->repoPath.'/file.txt', "hello\n");
        $this->git('add file.txt');
        $this->git('commit -q -m "initial commit"');
    }

    public function test_it_reports_not_dirty_for_a_clean_repo(): void
    {
        $this->initRepoWithCommit();

        $status = $this->service->forPath($this->repoPath);

        $this->assertFalse($status['dirty']);
    }

    public function test_it_reports_dirty_when_there_is_an_untracked_file(): void
    {
        $this->initRepoWithCommit();
        file_put_contents($this->repoPath.'/untracked.txt', "wip\n");

        $status = $this->service->forPath($this->repoPath);

        $this->assertTrue($status['dirty']);
    }

    public function test_it_reports_dirty_when_a_tracked_file_is_modified(): void
    {
        $this->initRepoWithCommit();
        file_put_contents($this->repoPath.'/file.txt', "changed\n");

        $status = $this->service->forPath($this->repoPath);

        $this->assertTrue($status['dirty']);
    }

    public function test_it_reports_no_upstream_when_branch_has_no_tracking_branch(): void
    {
        $this->initRepoWithCommit();

        $status = $this->service->forPath($this->repoPath);

        $this->assertFalse($status['has_upstream']);
        $this->assertNull($status['ahead']);
        $this->assertNull($status['behind']);
    }

    public function test_it_reports_ahead_and_behind_against_the_upstream_branch(): void
    {
        $remotePath = sys_get_temp_dir().'/git_status_test_remote_'.uniqid();
        mkdir($remotePath, 0777, true);
        $this->git('init -q --bare -b main', $remotePath);

        $this->initRepoWithCommit();
        $this->git('remote add origin '.escapeshellarg($remotePath));
        $this->git('push -q -u origin main');

        // Local moves ahead by one commit.
        file_put_contents($this->repoPath.'/file.txt', "local change\n");
        $this->git('commit -q -am "local commit"');

        // Remote moves ahead by one commit via a second clone.
        $clonePath = sys_get_temp_dir().'/git_status_test_clone_'.uniqid();
        $this->git('clone -q '.escapeshellarg($remotePath).' '.escapeshellarg($clonePath));
        $this->git('config user.email test@example.com', $clonePath);
        $this->git('config user.name "Test User"', $clonePath);
        file_put_contents($clonePath.'/other.txt', "remote change\n");
        $this->git('add other.txt', $clonePath);
        $this->git('commit -q -m "remote commit"', $clonePath);
        $this->git('push -q origin main', $clonePath);
        $this->git('fetch -q origin');

        $status = $this->service->forPath($this->repoPath);

        $this->assertTrue($status['has_upstream']);
        $this->assertSame(1, $status['ahead']);
        $this->assertSame(1, $status['behind']);

        $this->deleteDirectory($remotePath);
        $this->deleteDirectory($clonePath);
    }

    public function test_it_returns_nulls_for_a_path_that_is_not_a_git_repository(): void
    {
        $notARepo = sys_get_temp_dir().'/git_status_test_norepo_'.uniqid();
        mkdir($notARepo, 0777, true);

        $status = $this->service->forPath($notARepo);

        $this->assertNull($status['dirty']);
        $this->assertFalse($status['has_upstream']);
        $this->assertNull($status['ahead']);
        $this->assertNull($status['behind']);

        $this->deleteDirectory($notARepo);
    }

    public function test_it_scopes_dirty_to_the_service_directory_inside_a_monorepo(): void
    {
        // Serviço sem .git próprio dentro de um monorepo: o status precisa ser o
        // da subpasta, senão qualquer mexida num irmão suja o card de todos.
        $this->overrideScanBasePath(dirname($this->repoPath));

        mkdir($this->repoPath.'/api', 0777, true);
        mkdir($this->repoPath.'/sith', 0777, true);
        $this->git('init -q -b main');
        $this->git('config user.email test@example.com');
        $this->git('config user.name "Test User"');
        file_put_contents($this->repoPath.'/api/composer.json', '{}');
        file_put_contents($this->repoPath.'/sith/composer.json', '{}');
        $this->git('add .');
        $this->git('commit -q -m "initial commit"');

        file_put_contents($this->repoPath.'/api/wip.php', '<?php');

        $this->assertTrue($this->service->forPath($this->repoPath.'/api')['dirty']);
        $this->assertFalse($this->service->forPath($this->repoPath.'/sith')['dirty']);
    }

    protected function overrideScanBasePath(string $path): void
    {
        config(['services.scanner.base_path' => $path]);
    }
}
