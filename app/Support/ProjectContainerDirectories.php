<?php

namespace App\Support;

class ProjectContainerDirectories
{
    /**
     * Directories under SCAN_BASE_PATH that hold projects instead of being one:
     * the scanner descends one level into them and never imports the container
     * itself, even when it is a git repository of its own (a monorepo whose
     * services are the projects people actually work on).
     */
    public const DIRECTORIES = [
        'docker', 'microservices',
    ];
}
