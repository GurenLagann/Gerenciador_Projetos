<?php

namespace App\Services;

use App\Support\GitRepositoryRoot;

class GitStatusService
{
    /**
     * Live git status for a repository path: whether the working tree has
     * uncommitted changes, and how far ahead/behind it is from its upstream
     * branch (compared against the last-known remote-tracking ref — no
     * network fetch is performed).
     *
     * @return array{dirty: bool|null, has_upstream: bool, ahead: int|null, behind: int|null}
     */
    public function forPath(string $fullPath): array
    {
        $root = GitRepositoryRoot::for($fullPath);
        if ($root === null) {
            return ['dirty' => null, 'has_upstream' => false, 'ahead' => null, 'behind' => null];
        }

        $git = fn (string $cmd) => shell_exec("git -c safe.directory='*' -C ".escapeshellarg($fullPath)." $cmd 2>/dev/null");

        // Serviço sem .git próprio dentro de um monorepo: escopa na subpasta, senão
        // uma mexida num irmão marcaria todos os cards como dirty.
        $only = $root === realpath($fullPath) ? '' : ' -- .';

        $porcelain = trim($git('status --porcelain'.$only) ?? '');
        $dirty = $porcelain !== '';

        $leftRight = trim($git('rev-list --left-right --count @{u}...HEAD'.$only) ?? '');
        $hasUpstream = false;
        $ahead = null;
        $behind = null;

        if ($leftRight !== '' && preg_match('/^(\d+)\s+(\d+)$/', $leftRight, $matches)) {
            $hasUpstream = true;
            $behind = (int) $matches[1];
            $ahead = (int) $matches[2];
        }

        return ['dirty' => $dirty, 'has_upstream' => $hasUpstream, 'ahead' => $ahead, 'behind' => $behind];
    }
}
