<?php

namespace Tests\Unit\Services;

use App\Services\RepositorySizeService;
use Tests\TestCase;

class RepositorySizeServiceTest extends TestCase
{
    protected string $path;

    protected RepositorySizeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().'/repo_size_test_'.uniqid();
        mkdir($this->path, 0777, true);

        $this->service = new RepositorySizeService;
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->path);

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
            $itemPath = $dir.'/'.$item;
            is_dir($itemPath) ? $this->deleteDirectory($itemPath) : unlink($itemPath);
        }

        rmdir($dir);
    }

    protected function putFile(string $relativePath, int $bytes): void
    {
        $fullPath = $this->path.'/'.$relativePath;
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, str_repeat('a', $bytes));
    }

    public function test_it_sums_file_sizes_in_the_project_tree(): void
    {
        $this->putFile('app/Foo.php', 100);
        $this->putFile('app/Bar.php', 50);

        $size = $this->service->forPath($this->path);

        $this->assertSame(150, $size);
    }

    public function test_it_excludes_dependency_directories_from_the_total(): void
    {
        $this->putFile('app/Foo.php', 100);
        $this->putFile('vendor/some-package/Lib.php', 5000);
        $this->putFile('node_modules/some-lib/index.js', 5000);

        $size = $this->service->forPath($this->path);

        $this->assertSame(100, $size);
    }

    public function test_it_returns_zero_for_an_empty_directory(): void
    {
        $size = $this->service->forPath($this->path);

        $this->assertSame(0, $size);
    }

    public function test_it_returns_zero_for_a_path_that_does_not_exist(): void
    {
        $size = $this->service->forPath($this->path.'/does-not-exist');

        $this->assertSame(0, $size);
    }
}
