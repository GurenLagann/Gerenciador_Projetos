<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Services\ProjectScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * O scanner lê os manifestos do projeto (composer.json, requirements.txt,
 * package.json, .tool-versions) e traduz o que reconhece em chips. O que não
 * está na tabela de reconhecimento é ignorado — é o que mantém biblioteca de
 * apoio (orjson, httpx, nanoid) fora do card.
 */
class ProjectScannerServiceStackDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->basePath = sys_get_temp_dir().'/scanner_stack_test_'.uniqid();
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

    protected function stackOf(string $path): array
    {
        return Project::where('path', $path)->firstOrFail()->tech_stack;
    }

    public function test_it_reads_the_python_stack_from_requirements_and_tool_versions(): void
    {
        // Recorte real do requirements.txt da mensageria.
        $this->makeRepository('mensageria', [
            '.tool-versions' => "python 3.13.3\n",
            'requirements.txt' => <<<'TXT'
                # ── Web framework ───────────────
                fastapi>=0.111.0
                uvicorn[standard]>=0.29.0

                pydantic>=2.7.0
                sqlalchemy>=2.0.30
                alembic>=1.13.1
                pymysql>=1.1.1
                redis>=5.0.4
                confluent-kafka>=2.4.0
                orjson>=3.10.3
                httpx>=0.27.0
                pyjwt>=2.8.0
                nanoid>=2.0.0
                pytest>=8.0.0
                TXT,
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(
            ['Python 3.13', 'FastAPI', 'SQLAlchemy', 'Alembic', 'Pydantic', 'Kafka', 'Redis', 'MySQL', 'pytest'],
            $this->stackOf('mensageria'),
        );
    }

    public function test_it_ignores_dependencies_that_are_not_in_the_recognition_table(): void
    {
        $this->makeRepository('lib', [
            'requirements.txt' => "orjson>=3.10.3\nhttpx>=0.27.0\nnanoid>=2.0.0\npyjwt>=2.8.0\n",
        ]);

        // Sobra só a linguagem: nenhuma daquelas quatro vira chip.
        $this->assertSame(['Python'], $this->scanAndGet('lib'));
    }

    public function test_it_reads_the_php_stack_from_composer_and_tool_versions(): void
    {
        // Recorte real do composer.json do financeiro.
        $this->makeRepository('financeiro', [
            '.tool-versions' => "php 8.2.31\n",
            'spark' => "#!/usr/bin/env php\n",
            'composer.json' => json_encode([
                'require' => [
                    'php' => '^8.2',
                    'codeigniter4/framework' => '^4.7',
                    'doctrine/annotations' => '^2.0',
                    'zircote/swagger-php' => '^6.0',
                ],
                'require-dev' => [
                    'phpunit/phpunit' => '^10.5.16',
                    'fakerphp/faker' => '^1.9',
                ],
            ]),
        ]);

        $this->assertSame(
            ['PHP 8.2', 'CodeIgniter 4', 'Swagger', 'PHPUnit'],
            $this->scanAndGet('financeiro'),
        );
    }

    public function test_it_reads_the_framework_version_of_a_codeigniter_project(): void
    {
        $this->makeRepository('financeiro', [
            'composer.json' => json_encode(['require' => ['codeigniter4/framework' => '^4.7']]),
            'composer.lock' => json_encode(['packages' => [
                ['name' => 'codeigniter4/framework', 'version' => 'v4.7.0'],
            ]]),
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $this->assertSame(
            'CodeIgniter 4.7.0',
            Project::where('path', 'financeiro')->firstOrFail()->framework_version,
        );
    }

    public function test_a_node_project_gets_the_node_chips(): void
    {
        $this->makeRepository('front', [
            'package.json' => json_encode([
                'dependencies' => ['next' => '^14.0', 'react' => '^18.0'],
                'devDependencies' => ['typescript' => '^5.0', 'tailwindcss' => '^3.4'],
            ]),
        ]);

        $this->assertSame(
            ['Node.js', 'Next.js', 'React', 'TypeScript', 'Tailwind CSS'],
            $this->scanAndGet('front'),
        );
    }

    public function test_a_php_project_with_a_build_toolchain_is_not_labelled_node(): void
    {
        // O package.json aqui é só o Vite do asset pipeline — não faz do projeto um Node.
        $this->makeRepository('painel', [
            'artisan' => '',
            'composer.json' => json_encode(['require' => ['laravel/framework' => '^13.0']]),
            'package.json' => json_encode(['devDependencies' => ['vite' => '^8.0', 'tailwindcss' => '^4.0']]),
        ]);

        $stack = $this->scanAndGet('painel');

        $this->assertNotContains('Node.js', $stack);
        $this->assertContains('Laravel', $stack);
        $this->assertContains('Vite', $stack);
    }

    public function test_it_still_reports_unknown_when_there_is_no_manifest(): void
    {
        $this->makeRepository('so-docs', ['README.md' => '# nada aqui']);

        $this->assertSame(['Unknown'], $this->scanAndGet('so-docs'));
    }

    public function test_it_caps_the_number_of_chips(): void
    {
        $this->makeRepository('tudo', [
            '.tool-versions' => "python 3.13.3\n",
            'requirements.txt' => implode("\n", [
                'django', 'flask', 'fastapi', 'sqlalchemy', 'alembic', 'pydantic',
                'confluent-kafka', 'redis', 'pymysql', 'psycopg2', 'celery', 'pytest',
            ]),
            'Dockerfile' => "FROM python:3.13\n",
        ]);

        $this->assertCount(12, $this->scanAndGet('tudo'));
    }

    protected function scanAndGet(string $path): array
    {
        (new ProjectScannerService)->scan(dryRun: false);

        return $this->stackOf($path);
    }

    public function test_a_range_constraint_does_not_become_a_version(): void
    {
        // "^7.4 || ^8.0" não diz o que roda; o chip vira só a linguagem.
        $this->makeRepository('legado', [
            'composer.json' => json_encode(['require' => ['php' => '^7.4 || ^8.0']]),
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $project = Project::where('path', 'legado')->firstOrFail();
        $this->assertSame(['PHP'], $project->tech_stack);
        $this->assertNull($project->runtime_version);
    }

    public function test_a_single_constraint_still_becomes_a_version(): void
    {
        $this->makeRepository('moderno', [
            'composer.json' => json_encode(['require' => ['php' => '^8.2']]),
        ]);

        (new ProjectScannerService)->scan(dryRun: false);

        $project = Project::where('path', 'moderno')->firstOrFail();
        $this->assertSame(['PHP 8.2'], $project->tech_stack);
        $this->assertSame('PHP 8.2', $project->runtime_version);
    }
}
