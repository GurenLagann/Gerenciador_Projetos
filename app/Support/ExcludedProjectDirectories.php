<?php

namespace App\Support;

class ExcludedProjectDirectories
{
    /**
     * Directories treated as dependencies/build output rather than code the
     * project actually owns — skipped when scanning a project's own tree
     * (TODO/FIXME markers, disk size, etc).
     */
    public const DIRECTORIES = [
        '.git', 'vendor', 'node_modules', 'storage', 'dist', 'build',
        '.venv', 'venv', '__pycache__', 'target', '.next', '.nuxt',
    ];
}
