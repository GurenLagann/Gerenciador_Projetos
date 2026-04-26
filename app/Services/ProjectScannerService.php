<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
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

        if (!is_dir($this->basePath)) {
            return ['error' => "Base path not found: {$this->basePath}"];
        }

        $entries = scandir($this->basePath);

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;

            $fullPath = $this->basePath . '/' . $entry;

            if (!is_dir($fullPath)) continue;

            // Skip the project-manager itself
            if ($entry === 'project-manager') continue;

            $detected = $this->detectProject($fullPath, $entry);

            if ($detected) {
                $results[] = $detected;

                if (!$dryRun) {
                    $this->saveProject($detected);
                }
            }
        }

        // Also scan docker subdirectory
        $dockerPath = $this->basePath . '/docker';
        if (is_dir($dockerPath)) {
            $dockerEntries = scandir($dockerPath);
            foreach ($dockerEntries as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $fullPath = $dockerPath . '/' . $entry;
                if (!is_dir($fullPath)) continue;

                $detected = $this->detectProject($fullPath, "docker/{$entry}");
                if ($detected) {
                    $results[] = $detected;
                    if (!$dryRun) {
                        $this->saveProject($detected);
                    }
                }
            }
        }

        return $results;
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
            $composerContent = @json_decode(@file_get_contents($path . '/composer.json'), true);
            if ($composerContent) {
                $require = $composerContent['require'] ?? [];
                if (isset($require['laravel/framework'])) {
                    if (!in_array('Laravel', $techStack)) $techStack[] = 'Laravel';
                }
                if (isset($require['mongodb/mongodb']) || isset($require['jenssegers/mongodb'])) {
                    $techStack[] = 'MongoDB';
                }
            }
            if (!in_array('PHP', $techStack) && !in_array('Laravel', $techStack)) {
                $techStack[] = 'PHP';
            }
        }

        if (in_array('package.json', $files)) {
            $detectedFiles[] = 'package.json';
            $pkgContent = @json_decode(@file_get_contents($path . '/package.json'), true);
            if ($pkgContent) {
                $deps = array_merge(
                    $pkgContent['dependencies'] ?? [],
                    $pkgContent['devDependencies'] ?? []
                );
                if (isset($deps['next'])) $techStack[] = 'Next.js';
                elseif (isset($deps['react'])) $techStack[] = 'React';
                if (isset($deps['vue'])) $techStack[] = 'Vue.js';
                if (isset($deps['@angular/core'])) $techStack[] = 'Angular';
                if (array_key_exists('next', $deps) || array_key_exists('react', $deps) || array_key_exists('vue', $deps)) {
                    if (!in_array('Node.js', $techStack)) $techStack[] = 'Node.js';
                }
            }
        }

        if (in_array('pubspec.yaml', $files)) {
            $techStack[] = 'Flutter';
            $detectedFiles[] = 'pubspec.yaml';
        }

        if (in_array('Dockerfile', $files)) {
            $detectedFiles[] = 'Dockerfile';
            $dockerContent = @file_get_contents($path . '/Dockerfile');
            if ($dockerContent && preg_match('/FROM php:(\d+\.\d+)/i', $dockerContent, $matches)) {
                if (!in_array('PHP', $techStack) && !in_array('Laravel', $techStack)) {
                    $techStack[] = 'PHP ' . $matches[1];
                }
            }
            if (!in_array('Docker', $techStack)) $techStack[] = 'Docker';
        }

        if (in_array('docker-compose.yml', $files) || in_array('docker-compose.yaml', $files)) {
            $composeName = in_array('docker-compose.yml', $files) ? 'docker-compose.yml' : 'docker-compose.yaml';
            $detectedFiles[] = $composeName;
            if (!in_array('Docker', $techStack)) $techStack[] = 'Docker';
        }

        if (empty($techStack)) {
            $techStack[] = 'Unknown';
        }

        $humanName = $this->humanizeName(basename($dirName));

        return [
            'name' => $humanName,
            'slug' => Str::slug($humanName . '-' . Str::random(4)),
            'path' => $dirName,
            'tech_stack' => $techStack,
            'detected_files' => $detectedFiles,
            'git_info' => $this->readGitInfo($path),
            'is_scanned' => true,
            'last_scanned_at' => now(),
            'status' => $this->guessStatus($files, $path),
        ];
    }

    protected function readGitInfo(string $path): ?array
    {
        // Check if it's a git repo
        if (!is_dir($path . '/.git')) {
            return null;
        }

        $git = fn(string $cmd) => trim(shell_exec("git -c safe.directory='*' -C " . escapeshellarg($path) . " $cmd 2>/dev/null") ?? '');

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
            if (empty($line)) continue;
            $parts = explode('|', $line, 4);
            if (count($parts) < 4) continue;
            $commits[] = [
                'hash'    => $parts[0],
                'message' => $parts[1],
                'author'  => $parts[2],
                'date'    => $parts[3],
            ];
        }

        return [
            'branch'        => $branch,
            'total_commits' => $totalCommits,
            'commits'       => $commits,
        ];
    }

    protected function guessStatus(array $files, string $path): string
    {
        // Check for signs of archived/incomplete projects
        if (in_array('.archived', $files)) return 'archived';

        // Check if project has very few files (incomplete)
        $realFiles = array_filter($files, fn($f) => !in_array($f, ['.', '..', '.git', '.gitignore']));
        if (count($realFiles) <= 3) return 'idea';

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
        $existing = Project::where('path', $data['path'])->first();

        if ($existing) {
            $existing->update([
                'tech_stack'      => $data['tech_stack'],
                'detected_files'  => $data['detected_files'],
                'git_info'        => $data['git_info'],
                'is_scanned'      => true,
                'last_scanned_at' => now(),
            ]);
            return $existing;
        }

        // Ensure unique slug
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $i = 1;
        while (Project::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }

        return Project::create([
            'name'            => $data['name'],
            'slug'            => $slug,
            'path'            => $data['path'],
            'tech_stack'      => $data['tech_stack'],
            'detected_files'  => $data['detected_files'],
            'git_info'        => $data['git_info'],
            'status'          => $data['status'],
            'is_scanned'      => true,
            'last_scanned_at' => now(),
        ]);
    }
}
