<?php

namespace App\Services;

use App\Support\ExcludedProjectDirectories;

class TechnicalDebtSignalService
{
    /**
     * Counts TODO/FIXME markers in a project's source tree.
     *
     * @return array{todo: int, fixme: int, total: int}
     */
    public function forPath(string $fullPath): array
    {
        if (! is_dir($fullPath)) {
            return ['todo' => 0, 'fixme' => 0, 'total' => 0];
        }

        $excludeFlags = implode(' ', array_map(
            fn (string $dir) => '--exclude-dir='.escapeshellarg($dir),
            ExcludedProjectDirectories::DIRECTORIES
        ));
        $cmd = "grep -rIoE $excludeFlags '\\bTODO\\b|\\bFIXME\\b' ".escapeshellarg($fullPath).' 2>/dev/null';
        $output = shell_exec($cmd) ?? '';

        $matches = array_filter(explode("\n", trim($output)), fn ($line) => $line !== '');

        $todo = count(array_filter($matches, fn ($m) => str_ends_with($m, 'TODO')));
        $fixme = count(array_filter($matches, fn ($m) => str_ends_with($m, 'FIXME')));

        return ['todo' => $todo, 'fixme' => $fixme, 'total' => $todo + $fixme];
    }
}
