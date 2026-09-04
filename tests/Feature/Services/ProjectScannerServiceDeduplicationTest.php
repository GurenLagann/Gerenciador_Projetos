<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Services\ProjectScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Duas entradas que resolvem para o mesmo diretório são um projeto só, e a
 * blacklist mantém fora do dashboard (e portanto fora das ferramentas MCP) o
 * que o usuário nunca quer ver.
 */
class ProjectScannerServiceDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected string $outsidePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/scanner_dedup_test_'.uniqid();
        $this->outsidePath = sys_get_temp_dir().'/scanner_dedup_outside_'.uniqid();
        mkdir($this->basePath, 0777, true);
        mkdir($this->outsidePath, 0777, true);
        config(['services.scanner.base_path' => $this->basePath]);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);
        $this->deleteDirectory($this->outsidePath);
        config(['services.scanner.base_path' => '/var/www/host_projects']);

        parent::tearDown();
    }

    /**
     * is_dir() segue symlink, então descer nele apagaria o alvo. Links são
     * removidos como arquivo, sempre, antes de qualquer teste de diretório.
     */
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

            if (is_link($path) || ! is_dir($path)) {
                unlink($path);

                continue;
            }

            $this->deleteDirectory($path);
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

    /** @return array<int, string> */
    protected function scannedPaths(): array
    {
        return Project::pluck('path')->sort()->values()->all();
    }

    public function test_it_imports_the_real_directory_and_not_the_symlink_pointing_at_it(): void
    {
        $this->makeRepository('dev-helloo-shp', ['composer.json' => '{}']);
        symlink($this->basePath.'/dev-helloo-shp', $this->basePath.'/helloo');

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(['dev-helloo-shp'], $this->scannedPaths());
    }

    public function test_it_imports_a_symlink_whose_target_lives_outside_the_base_path(): void
    {
        // Sem rival dentro do base_path, o link é o único nome que existe:
        // descartá-lo esconderia o projeto do dashboard.
        mkdir($this->outsidePath.'/externo/.git', 0777, true);
        file_put_contents($this->outsidePath.'/externo/composer.json', '{}');
        symlink($this->outsidePath.'/externo', $this->basePath.'/externo');

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(['externo'], $this->scannedPaths());
    }

    public function test_it_never_imports_a_blacklisted_entry(): void
    {
        config(['services.scanner.ignored_entries' => ['project-manager', 'projeto-pessoal']]);

        $this->makeRepository('trabalho', ['composer.json' => '{}']);
        $this->makeRepository('projeto-pessoal', ['composer.json' => '{}']);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(['trabalho'], $this->scannedPaths());
    }

    public function test_it_moves_an_existing_project_from_the_symlink_name_to_the_real_one(): void
    {
        // Instalação anterior importou o symlink; o registro carrega anotações,
        // marcos e débitos presos ao id. Colapsar o par não pode enterrá-lo.
        $project = Project::factory()->create([
            'path' => 'helloo',
            'is_scanned' => true,
        ]);

        $this->makeRepository('dev-helloo-shp', ['composer.json' => '{}']);
        symlink($this->basePath.'/dev-helloo-shp', $this->basePath.'/helloo');

        (new ProjectScannerService)->scan(dryRun: false);

        $fresh = Project::withTrashed()->find($project->id);

        $this->assertSame('dev-helloo-shp', $fresh->path, 'o registro deveria ter sido movido para o nome canônico');
        $this->assertNull($fresh->deleted_at, 'o registro não pode ser soft-deletado no processo');
        $this->assertSame(1, Project::count(), 'não pode nascer um projeto duplicado ao lado');
    }

    public function test_it_leaves_the_alias_row_alone_when_the_canonical_name_is_already_taken(): void
    {
        Project::factory()->create(['path' => 'dev-helloo-shp', 'is_scanned' => true]);
        $alias = Project::factory()->create(['path' => 'helloo', 'is_scanned' => true]);

        $this->makeRepository('dev-helloo-shp', ['composer.json' => '{}']);
        symlink($this->basePath.'/dev-helloo-shp', $this->basePath.'/helloo');

        (new ProjectScannerService)->scan(dryRun: false);

        // Duplicata verdadeira: nada é movido por cima do registro canônico.
        $this->assertSame('helloo', Project::withTrashed()->find($alias->id)->path);
        $this->assertSame(['dev-helloo-shp'], $this->scannedPaths());
    }

    public function test_it_does_not_resurrect_a_project_the_user_deleted_under_the_alias_name(): void
    {
        $deleted = Project::factory()->create(['path' => 'helloo', 'is_scanned' => true]);
        $deleted->delete();

        $this->makeRepository('dev-helloo-shp', ['composer.json' => '{}']);
        symlink($this->basePath.'/dev-helloo-shp', $this->basePath.'/helloo');

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertNotNull(Project::withTrashed()->find($deleted->id)->deleted_at);
        $this->assertSame('helloo', Project::withTrashed()->find($deleted->id)->path);
    }
}
