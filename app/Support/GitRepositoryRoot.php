<?php

namespace App\Support;

class GitRepositoryRoot
{
    /**
     * Resolves which repository a project directory belongs to, by walking up
     * until a `.git` is found. Services of a monorepo (microservices/api) have
     * no `.git` of their own — the repository is the parent — so callers need
     * the root to know whether their git commands must be scoped to a subpath.
     *
     * The walk stops below SCAN_BASE_PATH and never inspects the scan root
     * itself: a repository sitting at or above it would otherwise be
     * attributed to every project underneath.
     *
     * @return string|null Absolute path of the repository root, or null when
     *                     the directory belongs to no repository.
     */
    public static function for(string $path, ?string $boundary = null): ?string
    {
        $current = realpath($path);

        if ($current === false) {
            return null;
        }

        $boundary = realpath($boundary ?? config('services.scanner.base_path'));

        while (true) {
            if (is_dir($current.'/.git')) {
                return $current;
            }

            if ($boundary === false) {
                return null;
            }

            $parent = dirname($current);

            if ($parent === $current || $parent === $boundary) {
                return null;
            }

            if (! str_starts_with($parent.'/', $boundary.'/')) {
                return null;
            }

            $current = $parent;
        }
    }
}
