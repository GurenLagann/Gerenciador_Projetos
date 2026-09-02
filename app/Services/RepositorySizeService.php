<?php

namespace App\Services;

use App\Support\ExcludedProjectDirectories;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class RepositorySizeService
{
    /**
     * Total size in bytes of a project's own files, skipping dependency and
     * build-output directories entirely (never descends into them).
     */
    public function forPath(string $fullPath): int
    {
        if (! is_dir($fullPath)) {
            return 0;
        }

        $filtered = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
            function (SplFileInfo $current): bool {
                // Symlinks are never followed — a project's own size shouldn't
                // include whatever a link happens to point at, and directory
                // links are already skipped by RecursiveDirectoryIterator's
                // default hasChildren() behavior; this makes that explicit
                // and also covers symlinked files, which getSize() would
                // otherwise resolve through.
                if ($current->isLink()) {
                    return false;
                }

                // Diretório ilegível (ex.: writable/session 0700 de outro owner no mount
                // do host): descer nele faz o iterator lançar UnexpectedValueException.
                if ($current->isDir() && ! $current->isReadable()) {
                    return false;
                }

                if ($current->isDir() && in_array($current->getFilename(), ExcludedProjectDirectories::DIRECTORIES, true)) {
                    return false;
                }

                return true;
            }
        );

        $bytes = 0;

        $files = new RecursiveIteratorIterator(
            $filtered,
            RecursiveIteratorIterator::LEAVES_ONLY,
            // Rede de segurança para o que escapar do filtro (permissão que muda
            // entre o teste e a descida): pula a subárvore em vez de estourar.
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                $bytes += $file->getSize();
            }
        }

        return $bytes;
    }
}
