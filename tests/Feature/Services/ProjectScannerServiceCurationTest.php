<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Services\ProjectScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Curadoria ganha do scanner: o que já está salvo (vindo do MCP / do cérebro)
 * nunca é sobrescrito nem apagado por uma varredura. O scanner só preenche
 * campo vazio e acrescenta ao tech_stack o que ainda não está lá.
 */
class ProjectScannerServiceCurationTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/scanner_curation_test_'.uniqid();
        mkdir($this->basePath, 0777, true);
        config(['services.scanner.base_path' => $this->basePath]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);
        config(['services.scanner.base_path' => '/var/www/host_projects']);

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
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    protected function makeRepository(string $name, array $files = []): void
    {
        mkdir($this->basePath.'/'.$name.'/.git', 0777, true);

        foreach ($files as $file => $contents) {
            file_put_contents($this->basePath.'/'.$name.'/'.$file, $contents);
        }
    }

    public function test_it_keeps_the_curated_tech_stack_and_only_appends_what_is_new(): void
    {
        $project = Project::factory()->create([
            'path' => 'financeiro',
            'is_scanned' => true,
            'tech_stack' => ['PHP 8.2', 'CodeIgniter 4', 'MySQL', 'SAP 4HANA'],
        ]);

        // A detecção só sabe dizer "PHP" (composer.json) e "Docker" (Dockerfile).
        $this->makeRepository('financeiro', [
            'composer.json' => '{}',
            'Dockerfile' => "FROM alpine\n",
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(
            ['PHP 8.2', 'CodeIgniter 4', 'MySQL', 'SAP 4HANA', 'Docker'],
            $project->fresh()->tech_stack,
        );
    }

    public function test_it_does_not_overwrite_curated_versions_with_detected_ones(): void
    {
        $project = Project::factory()->create([
            'path' => 'financeiro',
            'is_scanned' => true,
            'tech_stack' => ['PHP 8.2'],
            'framework_version' => 'CodeIgniter 4',
            'database_engine' => 'MySQL',
        ]);

        $this->makeRepository('financeiro', [
            'composer.json' => '{}',
            '.env' => "DB_CONNECTION=sqlite\n",
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $fresh = $project->fresh();
        $this->assertSame('CodeIgniter 4', $fresh->framework_version);
        $this->assertSame('MySQL', $fresh->database_engine);
    }

    public function test_it_still_fills_fields_that_are_empty(): void
    {
        $project = Project::factory()->create([
            'path' => 'novo',
            'is_scanned' => true,
            'tech_stack' => [],
            'database_engine' => null,
        ]);

        $this->makeRepository('novo', [
            'composer.json' => '{}',
            '.env' => "DB_CONNECTION=pgsql\n",
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $fresh = $project->fresh();
        $this->assertSame(['PHP'], $fresh->tech_stack);
        $this->assertSame('PostgreSQL', $fresh->database_engine);
    }

    public function test_it_keeps_updating_the_facts_it_owns(): void
    {
        $project = Project::factory()->create([
            'path' => 'financeiro',
            'is_scanned' => true,
            'tech_stack' => ['PHP 8.2'],
            'size_bytes' => 1,
            'last_scanned_at' => now()->subDay(),
        ]);

        $this->makeRepository('financeiro', ['composer.json' => '{}']);

        (new ProjectScannerService)->scan(dryRun: false);

        $fresh = $project->fresh();
        $this->assertNotSame(1, $fresh->size_bytes);
        $this->assertTrue($fresh->last_scanned_at->isToday());
        $this->assertSame(['composer.json'], $fresh->detected_files);
    }

    public function test_it_does_not_add_unknown_to_a_curated_stack(): void
    {
        $project = Project::factory()->create([
            'path' => 'so-docs',
            'is_scanned' => true,
            'tech_stack' => ['SAP 4HANA'],
        ]);

        $this->makeRepository('so-docs');

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(['SAP 4HANA'], $project->fresh()->tech_stack);
    }

    public function test_a_more_specific_detection_refines_the_chip_in_place(): void
    {
        // "PHP" salvo + "PHP 8.2" detectado é a mesma tecnologia, não duas.
        $project = Project::factory()->create([
            'path' => 'financeiro',
            'is_scanned' => true,
            'tech_stack' => ['PHP', 'SAP 4HANA'],
        ]);

        $this->makeRepository('financeiro', [
            '.tool-versions' => "php 8.2.31\n",
            'composer.json' => '{}',
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(['PHP 8.2', 'SAP 4HANA'], $project->fresh()->tech_stack);
    }

    public function test_a_vaguer_detection_never_replaces_a_curated_chip(): void
    {
        $project = Project::factory()->create([
            'path' => 'mensageria',
            'is_scanned' => true,
            'tech_stack' => ['Redis pub/sub', 'SQLAlchemy 2.0'],
        ]);

        $this->makeRepository('mensageria', [
            'requirements.txt' => "redis>=5.0.4\nsqlalchemy>=2.0.30\n",
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(
            ['Redis pub/sub', 'SQLAlchemy 2.0', 'Python'],
            $project->fresh()->tech_stack,
        );
    }

    public function test_refining_a_chip_does_not_duplicate_one_that_already_exists(): void
    {
        // Resíduo de scan antigo: o genérico e o específico convivem na mesma lista.
        $project = Project::factory()->create([
            'path' => 'chamado-v2',
            'is_scanned' => true,
            'tech_stack' => ['PHP', 'PHP 8.1', 'CodeIgniter 4'],
        ]);

        $this->makeRepository('chamado-v2', [
            '.tool-versions' => "php 8.1.2\n",
            'composer.json' => json_encode(['require' => ['codeigniter4/framework' => '^4.6']]),
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(['PHP 8.1', 'CodeIgniter 4'], $project->fresh()->tech_stack);
    }
}
