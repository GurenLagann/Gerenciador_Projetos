<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Support\GitRepositoryRoot;
use App\Support\ProjectContainerDirectories;
use Illuminate\Support\Str;

class ProjectScannerService
{
    /**
     * Files that mark a directory as a project even without a .git of its own,
     * used only for children of a container directory (see
     * ProjectContainerDirectories): there the repository is the parent.
     *
     * @var array<int, string>
     */
    protected const PROJECT_MARKERS = [
        'composer.json', 'artisan', 'spark', 'package.json',
        'pyproject.toml', 'requirements.txt',
    ];

    protected string $basePath;

    public function __construct()
    {
        $this->basePath = config('services.scanner.base_path');
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

            if (in_array($entry, ProjectContainerDirectories::DIRECTORIES, true)) {
                // Container fica de fora do $visitedPaths de propósito: se ele já
                // tinha sido importado como projeto, o removeStaleProjects() abaixo
                // o remove agora que os filhos ocuparam o lugar dele.
                $this->scanContainer($fullPath, $entry, $dryRun, $results, $visitedPaths);

                continue;
            }

            $visitedPaths[] = $entry;
            $this->processEntry($fullPath, $entry, $dryRun, $results);
        }

        if (! $dryRun) {
            $this->removeStaleProjects($visitedPaths);
        }

        return $results;
    }

    /**
     * Descends one level into a container directory, importing its children as
     * projects with a "container/child" path.
     *
     * @param  array<int, mixed>  $results
     * @param  array<int, string>  $visitedPaths
     */
    protected function scanContainer(string $containerPath, string $containerName, bool $dryRun, array &$results, array &$visitedPaths): void
    {
        foreach (scandir($containerPath) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $containerPath.'/'.$entry;
            if (! is_dir($fullPath)) {
                continue;
            }

            $visitedPaths[] = "{$containerName}/{$entry}";
            $this->processEntry($fullPath, "{$containerName}/{$entry}", $dryRun, $results, insideContainer: true);
        }
    }

    /**
     * A directory is a project when it is a git repository. Inside a container
     * directory that rule would hide every service of a monorepo, since their
     * repository is the parent — there a project marker file is enough.
     */
    protected function looksLikeProject(string $fullPath, bool $insideContainer): bool
    {
        if (is_dir($fullPath.'/.git')) {
            return true;
        }

        if (! $insideContainer) {
            return false;
        }

        foreach (self::PROJECT_MARKERS as $marker) {
            if (file_exists($fullPath.'/'.$marker)) {
                return true;
            }
        }

        return false;
    }

    protected function processEntry(string $fullPath, string $dirName, bool $dryRun, array &$results, bool $insideContainer = false): void
    {
        if (! $this->looksLikeProject($fullPath, $insideContainer)) {
            // Não é um projeto: não importa pastas novas, e remove (soft delete)
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

    /**
     * Tabela de reconhecimento: pacote declarado no manifesto → chip.
     * O que não está aqui NÃO vira chip — é isso que mantém biblioteca de apoio
     * (orjson, httpx, nanoid, pyjwt) fora do card. A ordem do array é a ordem
     * em que os chips aparecem na tela.
     */
    protected const COMPOSER_PACKAGES = [
        'laravel/framework' => 'Laravel',
        'codeigniter4/framework' => 'CodeIgniter 4',
        'symfony/framework-bundle' => 'Symfony',
        'nuwave/lighthouse' => 'GraphQL (Lighthouse)',
        'livewire/livewire' => 'Livewire',
        'mongodb/mongodb' => 'MongoDB',
        'jenssegers/mongodb' => 'MongoDB',
        'zircote/swagger-php' => 'Swagger',
        'phpunit/phpunit' => 'PHPUnit',
        'pestphp/pest' => 'Pest',
    ];

    protected const PYTHON_PACKAGES = [
        'django' => 'Django',
        'flask' => 'Flask',
        'fastapi' => 'FastAPI',
        'sqlalchemy' => 'SQLAlchemy',
        'alembic' => 'Alembic',
        'pydantic' => 'Pydantic',
        'confluent-kafka' => 'Kafka',
        'kafka-python' => 'Kafka',
        'redis' => 'Redis',
        'pymysql' => 'MySQL',
        'mysqlclient' => 'MySQL',
        'psycopg' => 'PostgreSQL',
        'psycopg2' => 'PostgreSQL',
        'psycopg2-binary' => 'PostgreSQL',
        'celery' => 'Celery',
        'pytest' => 'pytest',
    ];

    protected const NPM_PACKAGES = [
        'next' => 'Next.js',
        'react' => 'React',
        'vue' => 'Vue.js',
        '@angular/core' => 'Angular',
        'svelte' => 'Svelte',
        'typescript' => 'TypeScript',
        'vite' => 'Vite',
        'tailwindcss' => 'Tailwind CSS',
    ];

    /** Teto de chips por projeto — o card mostra 4 e o resto vira "+N". */
    protected const MAX_TECH_STACK = 12;

    protected function detectProject(string $path, string $dirName): ?array
    {
        $files = is_dir($path) ? scandir($path) : [];
        $detectedFiles = [];
        $runtime = $this->detectRuntimeVersion($path, $files);

        $techStack = array_merge(
            $this->detectPhpStack($path, $files, $detectedFiles, $runtime),
            $this->detectPythonStack($path, $files, $detectedFiles, $runtime),
            $this->detectNodeStack($path, $files, $detectedFiles, $runtime),
            $this->detectPlatformStack($path, $files, $detectedFiles),
        );

        $techStack = array_slice(array_values(array_unique($techStack)), 0, self::MAX_TECH_STACK);

        if ($techStack === []) {
            $techStack[] = 'Unknown';
        }

        $humanName = $this->humanizeName(basename($dirName));

        return [
            'name' => $humanName,
            'slug' => Str::slug($humanName.'-'.Str::random(4)),
            'path' => $dirName,
            'tech_stack' => $techStack,
            'detected_files' => $detectedFiles,
            'runtime_version' => $runtime,
            'framework_version' => $this->detectFrameworkVersion($path, $files),
            'database_engine' => $this->detectDatabaseEngine($path, $files),
            'git_info' => $this->readGitInfo($path),
            'size_bytes' => app(RepositorySizeService::class)->forPath($path),
            'is_scanned' => true,
            'last_scanned_at' => now(),
            'status' => $this->guessStatus($files, $path),
        ];
    }

    protected function detectPhpStack(string $path, array $files, array &$detectedFiles, ?string $runtime): array
    {
        $chips = [];
        $packages = [];
        $isPhp = false;

        if (in_array('artisan', $files)) {
            $detectedFiles[] = 'artisan';
            $chips[] = 'Laravel';
            $isPhp = true;
        }

        if (in_array('spark', $files)) {
            $detectedFiles[] = 'spark';
            $isPhp = true;
        }

        if (in_array('composer.json', $files)) {
            $detectedFiles[] = 'composer.json';
            $isPhp = true;
            $composer = $this->readJson($path.'/composer.json');
            $packages = array_keys(array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []));
        }

        if (! $isPhp) {
            return [];
        }

        return array_merge(
            [$this->runtimeChip('PHP', $runtime)],
            $chips,
            $this->chipsFor($packages, self::COMPOSER_PACKAGES),
        );
    }

    protected function detectPythonStack(string $path, array $files, array &$detectedFiles, ?string $runtime): array
    {
        $packages = [];
        $isPython = false;

        foreach (['requirements.txt', 'pyproject.toml', 'setup.py', 'Pipfile'] as $manifest) {
            if (! in_array($manifest, $files)) {
                continue;
            }

            $detectedFiles[] = $manifest;
            $isPython = true;
            $packages = array_merge($packages, $this->pythonPackageNames((string) @file_get_contents($path.'/'.$manifest)));
        }

        if (! $isPython) {
            return [];
        }

        return array_merge(
            [$this->runtimeChip('Python', $runtime)],
            $this->chipsFor($packages, self::PYTHON_PACKAGES),
        );
    }

    protected function detectNodeStack(string $path, array $files, array &$detectedFiles, ?string $runtime): array
    {
        if (! in_array('package.json', $files)) {
            return [];
        }

        $detectedFiles[] = 'package.json';
        $pkg = $this->readJson($path.'/package.json');
        $packages = array_keys(array_merge($pkg['dependencies'] ?? [], $pkg['devDependencies'] ?? []));
        $chips = $this->chipsFor($packages, self::NPM_PACKAGES);

        // package.json num projeto PHP costuma ser só o pipeline de assets (Vite,
        // Tailwind): mantém as ferramentas, mas o projeto não vira "Node.js".
        $isNodeProject = ! in_array('composer.json', $files) && ! in_array('artisan', $files);

        return $isNodeProject
            ? array_merge([$this->runtimeChip('Node.js', $runtime)], $chips)
            : $chips;
    }

    protected function detectPlatformStack(string $path, array $files, array &$detectedFiles): array
    {
        $chips = [];

        if (in_array('pubspec.yaml', $files)) {
            $detectedFiles[] = 'pubspec.yaml';
            $chips[] = 'Flutter';
        }

        if (in_array('Dockerfile', $files)) {
            $detectedFiles[] = 'Dockerfile';
            $chips[] = 'Docker';
        }

        foreach (['docker-compose.yml', 'docker-compose.yaml'] as $compose) {
            if (in_array($compose, $files)) {
                $detectedFiles[] = $compose;
                $chips[] = 'Docker';
                break;
            }
        }

        return $chips;
    }

    /**
     * O primeiro chip é o runtime com versão quando ela foi detectada de fonte
     * confiável ("PHP 8.2"), senão só a linguagem ("PHP").
     */
    protected function runtimeChip(string $language, ?string $runtime): string
    {
        $prefix = $language === 'Node.js' ? 'Node' : $language;

        return $runtime && Str::startsWith($runtime, $prefix.' ') ? $runtime : $language;
    }

    /**
     * Traduz os pacotes do manifesto em chips, na ordem da tabela e sem repetir
     * (duas entradas podem apontar para o mesmo rótulo, como psycopg/psycopg2).
     */
    protected function chipsFor(array $packages, array $table): array
    {
        $packages = array_map(fn ($name) => strtolower(str_replace('_', '-', (string) $name)), $packages);
        $chips = [];

        foreach ($table as $package => $chip) {
            if (in_array($package, $packages, true) && ! in_array($chip, $chips, true)) {
                $chips[] = $chip;
            }
        }

        return $chips;
    }

    /**
     * Nomes de pacote de um requirements.txt ou pyproject.toml. Não é um parser
     * de TOML: como a tabela é uma allowlist, uma chave de configuração que não
     * seja nome de pacote conhecido simplesmente não casa com nada.
     */
    protected function pythonPackageNames(string $contents): array
    {
        $names = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));

            if ($line === '' || str_starts_with($line, '-')) {
                continue;
            }

            if (preg_match('/^["\']?([A-Za-z0-9][A-Za-z0-9._-]*)/', $line, $matches)) {
                $names[] = strtolower(str_replace('_', '-', $matches[1]));
            }
        }

        return $names;
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

            if (preg_match('/FROM\s+python:(\d+\.\d+)/i', $content, $matches)) {
                return 'Python '.$matches[1];
            }
        }

        // .tool-versions (asdf/mise) diz a versão que a máquina realmente usa —
        // mais confiável que a constraint declarada no manifesto.
        if (in_array('.tool-versions', $files)) {
            $content = (string) @file_get_contents($path.'/.tool-versions');

            foreach (['php' => 'PHP', 'python' => 'Python', 'nodejs' => 'Node', 'node' => 'Node'] as $tool => $label) {
                if (preg_match('/^'.$tool.'\s+(\d+\.\d+)/mi', $content, $matches)) {
                    return $label.' '.$matches[1];
                }
            }
        }

        if (in_array('composer.json', $files)) {
            $version = $this->versionFromConstraint($this->readJson($path.'/composer.json')['require']['php'] ?? null);
            if ($version) {
                return 'PHP '.$version;
            }
        }

        if (in_array('package.json', $files)) {
            $version = $this->versionFromConstraint($this->readJson($path.'/package.json')['engines']['node'] ?? null);
            if ($version) {
                return 'Node '.$version;
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
                if (($package['name'] ?? null) === 'codeigniter4/framework') {
                    return 'CodeIgniter '.ltrim((string) ($package['version'] ?? ''), 'v');
                }
            }
        }

        if (in_array('composer.json', $files)) {
            $require = $this->readJson($path.'/composer.json')['require'] ?? [];
            foreach (['laravel/framework' => 'Laravel', 'codeigniter4/framework' => 'CodeIgniter'] as $package => $label) {
                $version = $this->versionFromConstraint($require[$package] ?? null);
                if ($version) {
                    return $label.' '.$version;
                }
            }
        }

        if (in_array('package.json', $files)) {
            $deps = $this->readJson($path.'/package.json');
            $deps = array_merge($deps['dependencies'] ?? [], $deps['devDependencies'] ?? []);
            $version = $this->versionFromConstraint($deps['next'] ?? null);
            if ($version) {
                return 'Next.js '.$version;
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
        $root = GitRepositoryRoot::for($path);
        if ($root === null) {
            return null;
        }

        $git = fn (string $cmd) => trim(shell_exec("git -c safe.directory='*' -C ".escapeshellarg($path)." $cmd 2>/dev/null") ?? '');

        // Serviço de um monorepo: a história do repositório inteiro não é a dele,
        // então commits, contadores e autores saem escopados na subpasta. O branch
        // não, porque é literalmente o branch em que esse código está.
        $only = $root === realpath($path) ? '' : ' -- .';

        $branch = $git('rev-parse --abbrev-ref HEAD');
        if (empty($branch) || $branch === 'HEAD') {
            return null;
        }

        $totalCommits = (int) $git('rev-list --count HEAD'.$only);

        $authorsRaw = $git('log --format=%an'.$only);
        $contributors = array_values(array_unique(array_filter(array_map(
            'trim',
            explode("\n", $authorsRaw)
        ))));
        sort($contributors);

        // Last 10 commits: hash|subject|author|date
        $logRaw = $git('log -10 --format="%h|%s|%an|%ci"'.$only);
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
            'contributors' => $contributors,
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
            // Curadoria ganha: o scanner nunca sobrescreve nem apaga o que já está
            // salvo (veio do MCP / do cérebro). Ele só preenche vazio e acrescenta.
            $existing->update([
                'tech_stack' => $this->mergeTechStack($existing->tech_stack ?? [], $data['tech_stack']),
                'detected_files' => $data['detected_files'],
                'runtime_version' => $existing->runtime_version ?: $data['runtime_version'],
                'framework_version' => $existing->framework_version ?: $data['framework_version'],
                'database_engine' => $existing->database_engine ?: $data['database_engine'],
                'git_info' => $data['git_info'],
                'size_bytes' => $data['size_bytes'],
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
            'size_bytes' => $data['size_bytes'],
            'status_id' => ProjectStatus::where('code', $data['status'])->value('id'),
            'is_scanned' => true,
            'last_scanned_at' => now(),
        ]);
    }

    /**
     * Une o que a varredura detectou ao que já está salvo, sem perder curadoria.
     * Um item detectado é ignorado quando o salvo já o cobre de forma mais
     * específica — "PHP" não entra ao lado de "PHP 8.2" — e "Unknown" só entra
     * em projeto que ainda não tem stack nenhuma.
     */
    protected function mergeTechStack(array $existing, array $detected): array
    {
        $merged = array_values($existing);

        foreach ($detected as $tech) {
            if ($tech === 'Unknown' && $merged !== []) {
                continue;
            }

            $covered = false;

            foreach ($merged as $index => $current) {
                if (Str::startsWith(mb_strtolower($current), mb_strtolower($tech))) {
                    // O salvo já diz o mesmo ou mais: "Redis pub/sub" × "Redis".
                    $covered = true;
                    break;
                }

                if (Str::startsWith(mb_strtolower($tech), mb_strtolower($current).' ')) {
                    // O detectado refina o salvo, no lugar: "PHP" vira "PHP 8.2".
                    $merged[$index] = $tech;
                    $covered = true;
                    break;
                }
            }

            if (! $covered) {
                $merged[] = $tech;
            }
        }

        return array_values(array_unique($merged));
    }

    /**
     * Versão utilizável a partir de uma constraint declarada: "^8.2" vira "8.2".
     * Um range como "^7.4 || ^8.0" não diz o que roda — vira null, e o chip fica
     * só com o nome da linguagem.
     */
    protected function versionFromConstraint(?string $constraint): ?string
    {
        $constraint = trim((string) $constraint);

        if ($constraint === '' || preg_match('/[|,\s]/', $constraint)) {
            return null;
        }

        return preg_match('/(\d+(?:\.\d+)*)/', $constraint, $matches) ? $matches[1] : null;
    }
}
