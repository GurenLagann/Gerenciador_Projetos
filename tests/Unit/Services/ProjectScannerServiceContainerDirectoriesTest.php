<?php

namespace Tests\Unit\Services;

use App\Services\ProjectScannerService;
use Tests\TestCase;

class ProjectScannerServiceContainerDirectoriesTest extends TestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scanner_container_test_'.uniqid();
        mkdir($this->basePath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->basePath);
        $this->overrideScanBasePath('/var/www/host_projects');

        parent::tearDown();
    }

    /**
     * @see ProjectScannerServiceDetectionTest::overrideScanBasePath()
     */
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

    /**
     * @param  array<string, string>  $files
     */
    protected function makeDirectory(string $relativePath, array $files = [], bool $withGit = false): string
    {
        $fullPath = $this->basePath.'/'.$relativePath;
        mkdir($fullPath, 0777, true);

        if ($withGit) {
            mkdir($fullPath.'/.git', 0777, true);
        }

        foreach ($files as $name => $content) {
            file_put_contents($fullPath.'/'.$name, $content);
        }

        return $fullPath;
    }

    /**
     * @return array<int, string>
     */
    protected function scannedPaths(): array
    {
        $this->overrideScanBasePath($this->basePath);

        return array_column((new ProjectScannerService)->scan(dryRun: true), 'path');
    }

    public function test_it_imports_container_children_that_have_a_project_marker_but_no_git_of_their_own(): void
    {
        $this->makeDirectory('microservices', withGit: true);
        $this->makeDirectory('microservices/api', ['composer.json' => '{}', 'spark' => '#!/usr/bin/env php']);

        $this->assertContains('microservices/api', $this->scannedPaths());
    }

    public function test_it_ignores_container_children_without_a_project_marker(): void
    {
        $this->makeDirectory('microservices', withGit: true);
        $this->makeDirectory('microservices/sql', ['create_database.sql' => 'CREATE DATABASE x;']);
        $this->makeDirectory('microservices/tickets');

        $paths = $this->scannedPaths();

        $this->assertNotContains('microservices/sql', $paths);
        $this->assertNotContains('microservices/tickets', $paths);
    }

    public function test_it_does_not_import_the_container_directory_itself(): void
    {
        $this->makeDirectory('microservices', withGit: true);
        $this->makeDirectory('microservices/api', ['composer.json' => '{}']);
        $this->makeDirectory('docker', withGit: true);

        $paths = $this->scannedPaths();

        $this->assertNotContains('microservices', $paths);
        $this->assertNotContains('docker', $paths);
    }

    public function test_it_still_imports_container_children_that_do_have_their_own_git(): void
    {
        $this->makeDirectory('microservices', withGit: true);
        $this->makeDirectory('microservices/mensageria', ['pyproject.toml' => '[project]'], withGit: true);

        $this->assertContains('microservices/mensageria', $this->scannedPaths());
    }

    public function test_it_keeps_requiring_git_for_top_level_directories(): void
    {
        $this->makeDirectory('Gerenciador_Projetos', ['composer.json' => '{}', 'artisan' => ''], withGit: true);
        $this->makeDirectory('random_folder', ['composer.json' => '{}']);

        $paths = $this->scannedPaths();

        $this->assertContains('Gerenciador_Projetos', $paths);
        $this->assertNotContains('random_folder', $paths);
    }
}
