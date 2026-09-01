<?php

namespace Tests\Feature\Models;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectLiveTechnicalDebtSignalTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/project_debt_signal_test_'.uniqid();
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

    public function test_it_skips_the_grep_and_returns_zeros_for_a_project_without_git_info(): void
    {
        // Mirrors an idea-converted project: has a fake 'ideas/...' path with
        // no real directory on disk, and was never scanned (git_info null).
        $project = Project::factory()->create(['path' => 'ideas/some-idea', 'git_info' => null]);

        $signal = $project->liveTechnicalDebtSignal();

        $this->assertSame(['todo' => 0, 'fixme' => 0, 'total' => 0], $signal);
    }

    public function test_it_counts_markers_in_the_projects_real_directory(): void
    {
        mkdir($this->basePath.'/repo', 0777, true);
        file_put_contents($this->basePath.'/repo/file.php', "<?php\n// TODO: one\n// TODO: two\n// FIXME: three\n");

        $project = Project::factory()->create(['path' => 'repo', 'git_info' => ['branch' => 'main']]);

        $signal = $project->liveTechnicalDebtSignal();

        $this->assertSame(['todo' => 2, 'fixme' => 1, 'total' => 3], $signal);
    }
}
