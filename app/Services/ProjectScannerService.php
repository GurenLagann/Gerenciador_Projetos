<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Support\Str;

class ProjectScannerService
{
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = env('SCAN_BASE_PATH', '/var/www/host_projects');
    }

    public function scan(bool $dryRun = false): array
    {
        $results = [];
        $visitedPaths = [];

        if (! is_dir($this->basePath)) {
            return ['error' => "Base path not found: {$this->basePath}"];
        }

        foreach (scandir($this->basePath) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if ($entry === 'project-manager') {
                continue;
            }

            $fullPath = $this->basePath.'/'.$entry;
            if (! is_dir($fullPath)) {
                continue;
            }

            $visitedPaths[] = $entry;
            $this->processEntry($fullPath, $entry, $dryRun, $results);
        }

        // Also scan docker subdirectory
        $dockerPath = $this->basePath.'/docker';
        if (is_dir($dockerPath)) {
            foreach (scandir($dockerPath) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $fullPath = $dockerPath.'/'.$entry;
                if (! is_dir($fullPath)) {
                    continue;
                }

                $visitedPaths[] = "docker/{$entry}";
                $this->processEntry($fullPath, "docker/{$entry}", $dryRun, $results);
            }
        }

        if (! $dryRun) {
            $this->removeStaleProjects($visitedPaths);
        }

        return $results;
    }

    protected function processEntry(string $fullPath, string $dirName, bool $dryRun, array &$results): void
    {
        if (! is_dir($fullPath.'/.git')) {
            // Não é um repositório git: não importa pastas novas, e remove (soft delete)
            // qualquer projeto já importado com esse path que ainda não tenha sido removido.
            if (! $dryRun) {
                Project::where('path', $dirName)->first()?->delete();
            }

            return;
        }

        $detected = $this->detectProject($fullPath, $dirName);

        if ($detected) {
            $results[] = $detected;

            if (! $dryRun) {
                $this->saveProject($detected);
            }
        }
    }

    /**
     * Soft-deletes previously scanned projects whose directory disappeared
     * from the host entirely (renamed or removed) rather than just losing
     * its .git folder — those never pass through processEntry() above,
     * since scandir() no longer sees them at all.
     *
     * @param  array<int, string>  $visitedPaths
     */
    protected function removeStaleProjects(array $visitedPaths): void
    {
        Project::where('is_scanned', true)
            ->whereNotIn('path', $visitedPaths)
            ->get()
            ->each(fn (Project $project) => $project->delete());
    }

    protected function detectProject(string $path, string $dirName): ?array
    {
        $files = is_dir($path) ? scandir($path) : [];
        $techStack = [];
        $detectedFiles = [];

        // Check for various tech indicators
        if (in_array('artisan', $files)) {
            $techStack[] = 'Laravel';
            $detectedFiles[] = 'artisan';
        }

        if (in_array('composer.json', $files)) {
            $detectedFiles[] = 'composer.json';
            $composerContent = @json_decode(@file_get_contents($path.'/composer.json'), true);
            if ($composerContent) {
                $require = $composerContent['require'] ?? [];
                if (isset($require['laravel/framework'])) {
                    if (! in_array('Laravel', $techStack)) {
                        $techStack[] = 'Laravel';
                    }
                }
                if (isset($require['mongodb/mongodb']) || isset($require['jenssegers/mongodb'])) {
                    $techStack[] = 'MongoDB';
                }
            }
            if (! in_array('PHP', $techStack) && ! in_array('Laravel', $techStack)) {
                $techStack[] = 'PHP';
            }
        }

        if (in_array('package.json', $files)) {
            $detectedFiles[] = 'package.json';
            $pkgContent = @json_decode(@file_get_contents($path.'/package.json'), true);
            if ($pkgContent) {
                $deps = array_merge(
                    $pkgContent['dependencies'] ?? [],
                    $pkgContent['devDependencies'] ?? []
                );
                if (isset($deps['next'])) {
                    $techStack[] = 'Next.js';
                } elseif (isset($deps['react'])) {
                    $techStack[] = 'React';
                }
                if (isset($deps['vue'])) {
                    $techStack[] = 'Vue.js';
                }
                if (isset($deps['@angular/core'])) {
                    $techStack[] = 'Angular';
                }
                if (array_key_exists('next', $deps) || array_key_exists('react', $deps) || array_key_exists('vue', $deps)) {
                    if (! in_array('Node.js', $techStack)) {
                        $techStack[] = 'Node.js';
                    }
                }
            }
        }

        foreach (['requirements.txt', 'pyproject.toml', 'setup.py', 'Pipfile'] as $pythonFile) {
            if (in_array($pythonFile, $files)) {
                $detectedFiles[] = $pythonFile;
                if (! in_array('Python', $techStack)) {
                    $techStack[] = 'Python';
                }
            }
        }

        if (in_array('pubspec.yaml', $files)) {
            $techStack[] = 'Flutter';
            $detectedFiles[] = 'pubspec.yaml';
        }

        if (in_array('Dockerfile', $files)) {
            $detectedFiles[] = 'Dockerfile';
            $dockerContent = @file_get_contents($path.'/Dockerfile');
            if ($dockerContent && preg_match('/FROM php:(\d+\.\d+)/i', $dockerContent, $matches)) {
                if (! in_array('PHP', $techStack) && ! in_array('Laravel', $techStack)) {
                    $techStack[] = 'PHP '.$matches[1];
                }
            }
            if (! in_array('Docker', $techStack)) {
                $techStack[] = 'Docker';
            }
        }

        if (in_array('docker-compose.yml', $files) || in_array('docker-compose.yaml', $files)) {
            $composeName = in_array('docker-compose.yml', $files) ? 'docker-compose.yml' : 'docker-compose.yaml';
            $detectedFiles[] = $composeName;
            if (! in_array('Docker', $techStack)) {
                $techStack[] = 'Docker';
            }
        }

        if (empty($techStack)) {
            $techStack[] = 'Unknown';
        }

        $humanName = $this->humanizeName(basename($dirName));

        return [
            'name' => $humanName,
            'slug' => Str::slug($humanName.'-'.Str::random(4)),
            'path' => $dirName,
            'tech_stack' => $techStack,
            'detected_files' => $detectedFiles,
            'runtime_version' => $this->detectRuntimeVersion($path, $files),
            'framework_version' => $this->detectFrameworkVersion($path, $files),
            'database_engine' => $this->detectDatabaseEngine($path, $files),
            'git_info' => $this->readGitInfo($path),
            'is_scanned' => true,
            'last_scanned_at' => now(),
            'status' => $this->guessStatus($files, $path),
        ];
    }

    /**
     * Best-effort detection of the language/runtime version (e.g. "PHP 8.4",
     * "Node 20"). Prefers the Dockerfile actually used to build/run the
     * project (most accurate, reflects what's really deployed) over the
     * composer.json/package.json version constraint (a declared range, not
     * necessarily what's running).
     */
    protected function detectRuntimeVersion(string $path, array $files): ?string
    {
        foreach ($this->candidateDockerfiles($path, $files) as $dockerfilePath) {
            $content = @file_get_contents($dockerfilePath);
            if (! $content) {
                continue;
            }

            if (preg_match('/FROM\s+php:(\d+\.\d+)/i', $content, $matches)) {
                return 'PHP '.$matches[1];
            }

            if (preg_match('/FROM\s+node:(\d+(?:\.\d+)?)/i', $content, $matches)) {
                return 'Node '.$matches[1];
            }
        }

        if (in_array('composer.json', $files)) {
            $constraint = $this->readJson($path.'/composer.json')['require']['php'] ?? null;
            if ($constraint) {
                return 'PHP '.ltrim($constraint, '^~>=');
            }
        }

        if (in_array('package.json', $files)) {
            $constraint = $this->readJson($path.'/package.json')['engines']['node'] ?? null;
            if ($constraint) {
                return 'Node '.ltrim($constraint, '^~>=');
            }
        }

        return null;
    }

    /**
     * Best-effort detection of the main framework version (e.g. "Laravel
     * 13.3.0", "Next.js 14.2.0"). Prefers the lock file's pinned version
     * (what's actually installed) over the composer.json/package.json
     * declared constraint (a range).
     */
    protected function detectFrameworkVersion(string $path, array $files): ?string
    {
        if (in_array('composer.lock', $files)) {
            $lock = $this->readJson($path.'/composer.lock');
            foreach ($lock['packages'] ?? [] as $package) {
                if (($package['name'] ?? null) === 'laravel/framework') {
                    return 'Laravel '.ltrim((string) ($package['version'] ?? ''), 'v');
                }
            }
        }

        if (in_array('composer.json', $files)) {
            $constraint = $this->readJson($path.'/composer.json')['require']['laravel/framework'] ?? null;
            if ($constraint) {
                return 'Laravel '.ltrim($constraint, '^~>=');
            }
        }

        if (in_array('package.json', $files)) {
            $deps = $this->readJson($path.'/package.json');
            $deps = array_merge($deps['dependencies'] ?? [], $deps['devDependencies'] ?? []);
            if (isset($deps['next'])) {
                return 'Next.js '.ltrim($deps['next'], '^~>=');
            }
        }

        return null;
    }

    /**
     * Best-effort detection of the database engine. Checks the project's
     * own .env first (most accurate), falls back to docker-compose.yml
     * service images, and finally assumes SQLite for PHP/Laravel projects
     * with no other signal (Laravel's own default).
     */
    protected function detectDatabaseEngine(string $path, array $files): ?string
    {
        if (in_array('.env', $files)) {
            $env = @file_get_contents($path.'/.env');
            if ($env && preg_match('/^DB_CONNECTION=(.+)$/m', $env, $matches)) {
                $label = match (strtolower(trim($matches[1]))) {
                    'sqlite' => 'SQLite',
                    'mysql' => 'MySQL',
                    'pgsql' => 'PostgreSQL',
                    'mariadb' => 'MariaDB',
                    'mongodb' => 'MongoDB',
                    default => ucfirst(trim($matches[1])),
                };

                if (in_array($label, ['MySQL', 'PostgreSQL', 'MariaDB'])
                    && preg_match('/^DB_DATABASE=(.+)$/m', $env, $dbMatches)
                    && trim($dbMatches[1]) !== '') {
                    return "{$label} (".trim($dbMatches[1]).')';
                }

                return $label;
            }
        }

        $composeFile = match (true) {
            in_array('docker-compose.yml', $files) => $path.'/docker-compose.yml',
            in_array('docker-compose.yaml', $files) => $path.'/docker-compose.yaml',
            default => null,
        };

        if ($composeFile) {
            $compose = @file_get_contents($composeFile);
            if ($compose) {
                foreach (['mysql' => 'MySQL', 'postgres' => 'PostgreSQL', 'mariadb' => 'MariaDB', 'mongo' => 'MongoDB'] as $needle => $label) {
                    if (preg_match('/image:\s*'.$needle.'/i', $compose)) {
                        return $label;
                    }
                }
            }
        }

        if (in_array('artisan', $files) || in_array('composer.json', $files)) {
            return 'SQLite';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function candidateDockerfiles(string $path, array $files): array
    {
        $candidates = [];

        if (in_array('Dockerfile', $files)) {
            $candidates[] = $path.'/Dockerfile';
        }

        foreach (['docker/php/Dockerfile', 'docker/Dockerfile'] as $relative) {
            if (is_file($path.'/'.$relative)) {
                $candidates[] = $path.'/'.$relative;
            }
        }

        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readJson(string $path): array
    {
        return @json_decode(@file_get_contents($path), true) ?: [];
    }

    protected function readGitInfo(string $path): ?array
    {
        // Check if it's a git repo
        if (! is_dir($path.'/.git')) {
            return null;
        }

        $git = fn (string $cmd) => trim(shell_exec("git -c safe.directory='*' -C ".escapeshellarg($path)." $cmd 2>/dev/null") ?? '');

        $branch = $git('rev-parse --abbrev-ref HEAD');
        if (empty($branch) || $branch === 'HEAD') {
            return null;
        }

        $totalCommits = (int) $git('rev-list --count HEAD');

        // Last 10 commits: hash|subject|author|date
        $logRaw = $git('log -10 --format="%h|%s|%an|%ci"');
        $commits = [];
        foreach (explode("\n", $logRaw) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $parts = explode('|', $line, 4);
            if (count($parts) < 4) {
                continue;
            }
            $commits[] = [
                'hash' => $parts[0],
                'message' => $parts[1],
                'author' => $parts[2],
                'date' => $parts[3],
            ];
        }

        return [
            'branch' => $branch,
            'total_commits' => $totalCommits,
            'commits' => $commits,
        ];
    }

    protected function guessStatus(array $files, string $path): string
    {
        // Check for signs of archived/incomplete projects
        if (in_array('.archived', $files)) {
            return 'archived';
        }

        // Check if project has very few files (incomplete)
        $realFiles = array_filter($files, fn ($f) => ! in_array($f, ['.', '..', '.git', '.gitignore']));
        if (count($realFiles) <= 3) {
            return 'idea';
        }

        return 'in-progress';
    }

    protected function humanizeName(string $dirName): string
    {
        // Convert camelCase and PascalCase to spaces
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $dirName);
        // Convert underscores and hyphens to spaces
        $name = str_replace(['_', '-'], ' ', $name);

        // Title case
        return ucwords(strtolower($name));
    }

    protected function saveProject(array $data): Project
    {
        $existing = Project::withTrashed()->where('path', $data['path'])->first();

        if ($existing && $existing->trashed()) {
            // Removido manualmente pelo usuário — nunca mais recriar ou tocar.
            return $existing;
        }

        if ($existing) {
            $existing->update([
                'tech_stack' => $data['tech_stack'],
                'detected_files' => $data['detected_files'],
                'runtime_version' => $data['runtime_version'],
                'framework_version' => $data['framework_version'],
                'database_engine' => $data['database_engine'],
                'git_info' => $data['git_info'],
                'is_scanned' => true,
                'last_scanned_at' => now(),
            ]);

            return $existing;
        }

        // Ensure unique slug
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $i = 1;
        while (Project::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$i++;
        }

        return Project::create([
            'name' => $data['name'],
            'slug' => $slug,
            'path' => $data['path'],
            'tech_stack' => $data['tech_stack'],
            'detected_files' => $data['detected_files'],
            'runtime_version' => $data['runtime_version'],
            'framework_version' => $data['framework_version'],
            'database_engine' => $data['database_engine'],
            'git_info' => $data['git_info'],
            'status_id' => ProjectStatus::where('code', $data['status'])->value('id'),
            'is_scanned' => true,
            'last_scanned_at' => now(),
        ]);
    }
}
