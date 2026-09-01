<?php

namespace App\Services;

class TechnicalDebtSignalService
{
    /**
     * Directories skipped when scanning for TODO/FIXME markers — dependency
     * and build output, not code the project actually owns.
     */
    protected const EXCLUDED_DIRS = [
        '.git', 'vendor', 'node_modules', 'storage', 'dist', 'build',
        '.venv', 'venv', '__pycache__', 'target', '.next', '.nuxt',
    ];

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
            self::EXCLUDED_DIRS
        ));
        $cmd = "grep -rIoE $excludeFlags '\\bTODO\\b|\\bFIXME\\b' ".escapeshellarg($fullPath).' 2>/dev/null';
        $output = shell_exec($cmd) ?? '';

        $matches = array_filter(explode("\n", trim($output)), fn ($line) => $line !== '');

        $todo = count(array_filter($matches, fn ($m) => str_ends_with($m, 'TODO')));
        $fixme = count(array_filter($matches, fn ($m) => str_ends_with($m, 'FIXME')));

        return ['todo' => $todo, 'fixme' => $fixme, 'total' => $todo + $fixme];
    }
}
