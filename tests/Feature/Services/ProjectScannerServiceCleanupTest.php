<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Services\ProjectScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectScannerServiceCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/scanner_cleanup_test_'.uniqid();
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

    public function test_it_soft_deletes_previously_scanned_projects_whose_directory_no_longer_exists(): void
    {
        $project = Project::factory()->create([
            'path' => 'GoneApp',
            'is_scanned' => true,
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSoftDeleted($project);
    }

    public function test_it_does_not_touch_manually_created_projects_never_scanned(): void
    {
        $project = Project::factory()->create([
            'path' => 'ideas/some-converted-idea',
            'is_scanned' => false,
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_does_not_touch_already_trashed_projects(): void
    {
        $project = Project::factory()->create([
            'path' => 'AlreadyGone',
            'is_scanned' => true,
        ]);
        $project->delete();
        $deletedAt = $project->fresh()->deleted_at;

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame($deletedAt->toDateTimeString(), $project->fresh()->deleted_at->toDateTimeString());
    }

    public function test_it_soft_deletes_a_project_whose_directory_became_a_container(): void
    {
        // microservices era um projeto (tem .git próprio); agora seus serviços é que
        // são os projetos, e o card do guarda-chuva sai.
        $umbrella = Project::factory()->create([
            'path' => 'microservices',
            'is_scanned' => true,
        ]);

        mkdir($this->basePath.'/microservices/.git', 0777, true);
        mkdir($this->basePath.'/microservices/api', 0777, true);
        file_put_contents($this->basePath.'/microservices/api/composer.json', '{}');

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSoftDeleted($umbrella);
        $this->assertDatabaseHas('projects', [
            'path' => 'microservices/api',
            'deleted_at' => null,
        ]);
    }

    public function test_dry_run_does_not_soft_delete_missing_projects(): void
    {
        $project = Project::factory()->create([
            'path' => 'GoneAppDryRun',
            'is_scanned' => true,
        ]);

        (new ProjectScannerService)->scan(dryRun: true);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }
}
