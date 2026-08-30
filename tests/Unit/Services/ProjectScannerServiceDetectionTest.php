<?php

namespace Tests\Unit\Services;

use App\Services\ProjectScannerService;
use Tests\TestCase;

class ProjectScannerServiceDetectionTest extends TestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scanner_test_'.uniqid();
        mkdir($this->basePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);
        $this->overrideScanBasePath('/var/www/host_projects');

        parent::tearDown();
    }

    /**
     * ProjectScannerService reads SCAN_BASE_PATH via the env() helper, and
     * .env already defines it — putenv() alone isn't enough to override it
     * mid-process because Illuminate's Env repository prioritizes $_ENV/
     * $_SERVER (populated once at bootstrap) over a later putenv() call.
     */
    protected function overrideScanBasePath(string $path): void
    {
        putenv('SCAN_BASE_PATH='.$path);
        $_ENV['SCAN_BASE_PATH'] = $path;
        $_SERVER['SCAN_BASE_PATH'] = $path;
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

    protected function makeFakeProject(string $name, array $files): string
    {
        $projectPath = $this->basePath.'/'.$name;
        mkdir($projectPath, 0777, true);
        mkdir($projectPath.'/.git', 0777, true);

        foreach ($files as $relativePath => $content) {
            $fullPath = $projectPath.'/'.$relativePath;
            @mkdir(dirname($fullPath), 0777, true);
            file_put_contents($fullPath, $content);
        }

        return $projectPath;
    }

    public function test_it_detects_php_version_from_dockerfile_laravel_version_from_composer_lock_and_mysql_database(): void
    {
        $this->makeFakeProject('FakeLaravelApp', [
            'composer.json' => json_encode(['require' => ['php' => '^8.3', 'laravel/framework' => '^11.0']]),
            'composer.lock' => json_encode(['packages' => [
                ['name' => 'laravel/framework', 'version' => 'v11.31.0'],
            ]]),
            'Dockerfile' => "FROM php:8.3-fpm\nRUN echo hi",
            '.env' => "APP_NAME=Fake\nDB_CONNECTION=mysql\nDB_DATABASE=testdb\n",
        ]);

        $this->overrideScanBasePath($this->basePath);
        $results = (new ProjectScannerService)->scan(dryRun: true);

        $this->assertCount(1, $results);
        $this->assertSame('PHP 8.3', $results[0]['runtime_version']);
        $this->assertSame('Laravel 11.31.0', $results[0]['framework_version']);
        $this->assertSame('MySQL (testdb)', $results[0]['database_engine']);
    }

    public function test_it_falls_back_to_composer_json_constraints_when_no_dockerfile_or_lock_file(): void
    {
        $this->makeFakeProject('FakeConstraintApp', [
            'composer.json' => json_encode(['require' => ['php' => '^8.2', 'laravel/framework' => '^12.0']]),
        ]);

        $this->overrideScanBasePath($this->basePath);
        $results = (new ProjectScannerService)->scan(dryRun: true);

        $this->assertSame('PHP 8.2', $results[0]['runtime_version']);
        $this->assertSame('Laravel 12.0', $results[0]['framework_version']);
        $this->assertSame('SQLite', $results[0]['database_engine']);
    }

    public function test_it_detects_next_js_version_and_database_from_docker_compose(): void
    {
        $this->makeFakeProject('FakeNextApp', [
            'package.json' => json_encode(['dependencies' => ['next' => '^14.2.0']]),
            'docker-compose.yml' => "services:\n  db:\n    image: postgres:16\n",
        ]);

        $this->overrideScanBasePath($this->basePath);
        $results = (new ProjectScannerService)->scan(dryRun: true);

        $this->assertSame('Next.js 14.2.0', $results[0]['framework_version']);
        $this->assertSame('PostgreSQL', $results[0]['database_engine']);
    }
}
