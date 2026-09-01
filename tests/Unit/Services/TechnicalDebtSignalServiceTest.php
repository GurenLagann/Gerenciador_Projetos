<?php

namespace Tests\Unit\Services;

use App\Services\TechnicalDebtSignalService;
use Tests\TestCase;

class TechnicalDebtSignalServiceTest extends TestCase
{
    protected string $path;

    protected TechnicalDebtSignalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().'/debt_signal_test_'.uniqid();
        mkdir($this->path, 0777, true);

        $this->service = new TechnicalDebtSignalService;
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

    protected function putFile(string $relativePath, string $content): void
    {
        $fullPath = $this->path.'/'.$relativePath;
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, $content);
    }

    public function test_it_counts_todo_and_fixme_markers_across_files(): void
    {
        $this->putFile('app/Foo.php', "<?php\n// TODO: refactor this\nclass Foo {}\n");
        $this->putFile('app/Bar.php', "<?php\n// FIXME: broken edge case\n// TODO: add tests\nclass Bar {}\n");

        $signal = $this->service->forPath($this->path);

        $this->assertSame(2, $signal['todo']);
        $this->assertSame(1, $signal['fixme']);
        $this->assertSame(3, $signal['total']);
    }

    public function test_it_ignores_markers_inside_excluded_dependency_directories(): void
    {
        $this->putFile('app/Foo.php', "<?php\n// TODO: real one\n");
        $this->putFile('vendor/some-package/Lib.php', "<?php\n// TODO: noise from a dependency\n");
        $this->putFile('node_modules/some-lib/index.js', "// FIXME: noise from a dependency\n");

        $signal = $this->service->forPath($this->path);

        $this->assertSame(1, $signal['todo']);
        $this->assertSame(0, $signal['fixme']);
    }

    public function test_it_returns_zeros_when_there_are_no_markers(): void
    {
        $this->putFile('app/Foo.php', "<?php\nclass Foo {}\n");

        $signal = $this->service->forPath($this->path);

        $this->assertSame(['todo' => 0, 'fixme' => 0, 'total' => 0], $signal);
    }

    public function test_it_returns_zeros_for_a_path_that_does_not_exist(): void
    {
        $signal = $this->service->forPath($this->path.'/does-not-exist');

        $this->assertSame(['todo' => 0, 'fixme' => 0, 'total' => 0], $signal);
    }
}
