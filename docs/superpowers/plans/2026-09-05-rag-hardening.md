# RAG Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close débitos #51/#52/#53 (RAG index drift and a latent crash) with a permanent self-healing mechanism instead of one-off patches, and harden two infra weak points (`php-fpm`/CLI extension parity, queue worker code staleness) already paid for twice by this project.

**Architecture:** A new `EmbeddingIndexService::reconcile()` diffs the DB (source of truth) against Qdrant per `Searchable` type and dispatches the existing `IndexSearchableContent`/`RemoveFromSearchIndex` jobs to close any gap — including excluding annotations/milestones/technical debts whose parent `Project`/`Idea` was soft-deleted. A new `search:reconcile-index` command wraps it and runs daily via a new `scheduler` container (`php artisan schedule:work`). Two independent infra fixes (Dockerfile, docker-compose) close the extension-parity and worker-staleness gotchas. A small `FailedIndexingDebtRecorder` service, called from each job's `failed()` hook, turns a permanently-failed indexing job into a visible `TechnicalDebt` instead of a silent row in `failed_jobs`.

**Tech Stack:** Laravel 13 / PHP 8.4, Qdrant (HTTP API), Ollama (`bge-m3` embeddings), Docker Compose, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-05-rag-hardening-design.md`

## Global Constraints

- Point ID offsets per type are fixed and must not change: `project` +1_000_000_000, `idea` +2_000_000_000, `annotation` +3_000_000_000, `milestone` +4_000_000_000, `technical_debt` +5_000_000_000 (`EmbeddingIndexService::pointId()`).
- Qdrant collection name comes from `config('services.qdrant.collection')` (env `QDRANT_COLLECTION`, default `project_manager_content`) — never hardcode the literal string in new code.
- `annotation`, `milestone`, and `technical_debt` all depend on a `Project`/`Idea` parent that can be soft-deleted; `project` and `idea` do not. Any code that decides "should this record be indexed" must apply the parent-liveness check to all three dependent types, not just `annotation`.
- No new external monitoring/alerting service (Horizon, Slack, email) — visibility into failures goes through the project's own `TechnicalDebt` board, per the spec's explicit scope decision.
- `TechnicalDebt` only has a `title` column (no body/description) — anything communicated by an automatically-created debt must fit in the title string.
- Queue tests in this codebase run with `QUEUE_CONNECTION=sync` by default (`phpunit.xml`), so any test asserting dispatch behavior must call `Queue::fake()` explicitly (see existing `SearchIndexingObserverTest`).

---

### Task 1: Null-safe title fallback in `Annotation::searchableContent()` (débito #53)

**Files:**
- Modify: `app/Models/Annotation.php:32`
- Test: `tests/Feature/Models/AnnotationSearchableContentTest.php` (new)

**Interfaces:**
- Consumes: nothing new.
- Produces: no interface change — same signature `Annotation::searchableContent(): array{0: ?string, 1: ?string}`, now null-safe.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Models/AnnotationSearchableContentTest.php`:

```php
<?php

namespace Tests\Feature\Models;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnotationSearchableContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_searchable_content_falls_back_to_a_generic_title_when_the_parent_is_soft_deleted_and_the_annotation_has_no_title(): void
    {
        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $annotation = $project->annotations()->create(['content' => 'Some note']);

        $project->delete();

        [$title, $text] = $annotation->fresh()->searchableContent();

        $this->assertSame('Annotation', $title);
        $this->assertSame('Some note', $text);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec php php artisan test --filter=AnnotationSearchableContentTest`
Expected: FAIL with an `Error` — `Attempt to read property "name" on null` (or `"title"` on null), thrown from `app/Models/Annotation.php:32`.

- [ ] **Step 3: Write minimal implementation**

In `app/Models/Annotation.php`, replace line 32:

```php
        $title = $this->title ?: ($this->annotatable?->name ?? $this->annotatable?->title ?? 'Annotation');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec php php artisan test --filter=AnnotationSearchableContentTest`
Expected: PASS.

- [ ] **Step 5: Run the full suite to check for regressions**

Run: `docker compose exec php php artisan test`
Expected: all tests pass (same count as before the change, plus this new one).

- [ ] **Step 6: Commit**

```bash
git add app/Models/Annotation.php tests/Feature/Models/AnnotationSearchableContentTest.php
git commit -m "Fix crash risk: null-safe title fallback when annotation's parent is soft-deleted (debt #53)"
```

---

### Task 2: `ext-intl` parity between CLI and `php-fpm`

No PHPUnit test applies here — this is an OS/extension-level change. Verification is operational (rebuild the image, check the module list, hit an intl-dependent code path through `php-fpm`, not just CLI).

**Files:**
- Modify: `Dockerfile:9`

**Interfaces:** none — infra-only change.

- [ ] **Step 1: Confirm the current gap**

Run: `docker compose exec php php -m | grep -i intl`
Expected: no output (extension absent from the image `php-fpm` actually serves).

- [ ] **Step 2: Add the extension**

In `Dockerfile`, add `libicu-dev` to the apt package list (line 3-5) and `intl` to the extension install list (line 9):

```dockerfile
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libicu-dev \
    zip unzip libsqlite3-dev nodejs npm

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd intl
```

- [ ] **Step 3: Rebuild the image and verify the extension is present**

Run: `docker compose build php queue-worker`
Run: `docker compose up -d php queue-worker`
Run: `docker compose exec php php -m | grep -i intl`
Expected: `intl` is printed.

- [ ] **Step 4: Verify it works from php-fpm, not just CLI**

Run: `docker compose exec -T php php -r "echo \Illuminate\Support\Number::fileSize(123456789);"`
Expected: prints a formatted size (e.g. `117.74 MB`) with no fatal error. This confirms the fix from the same binary path (`php` CLI inside the image) that previously lacked `intl`; combine with a real browser/`curl` hit against `localhost:8082` on any page rendering a project's disk size (the marco #54 feature) to confirm `php-fpm` itself serves it without a 500.

- [ ] **Step 5: Run the full test suite (no regression expected, but confirms the rebuilt image still runs the app correctly)**

Run: `docker compose exec php php artisan test`
Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add Dockerfile
git commit -m "Add ext-intl to the php-fpm image to match the CLI binary used by PHPUnit"
```

---

### Task 3: Queue worker auto-restart (`--max-jobs`)

No PHPUnit test applies — this is a process-supervision change. Verification is operational.

**Files:**
- Modify: `docker-compose.yml:47`

**Interfaces:** none — infra-only change.

- [ ] **Step 1: Make the change**

In `docker-compose.yml`, change the `queue-worker` service's `command`:

```yaml
    command: php artisan queue:work --sleep=3 --tries=3 --max-jobs=100
```

- [ ] **Step 2: Apply and verify the worker restarts after the job budget**

Run: `docker compose up -d queue-worker`
Run: `docker compose logs -f queue-worker` (watch while triggering ~100 indexing jobs, e.g. via `docker compose exec php php artisan search:rebuild-index` from Task 4 onward, or any bulk save)
Expected: after roughly 100 processed jobs, the log shows the worker process exit and `restart: unless-stopped` bring up a fresh one — confirm with `docker compose ps queue-worker` showing a recent `Up` time and `docker inspect pm_queue_worker --format '{{.RestartCount}}'` incrementing over time.

- [ ] **Step 3: Commit**

```bash
git add docker-compose.yml
git commit -m "Auto-recycle the queue worker every 100 jobs so it always picks up current code (closes the #41 gotcha structurally)"
```

---

### Task 4: RAG index reconciliation (débitos #51 and #52)

**Files:**
- Modify: `app/Services/EmbeddingIndexService.php` (add imports, `indexedSourceIds()`, `reconcile()`)
- Create: `app/Console/Commands/ReconcileSearchIndexCommand.php`
- Modify: `routes/console.php` (register the daily schedule)
- Modify: `docker-compose.yml` (add a `scheduler` service running `php artisan schedule:work`)
- Test: `tests/Feature/Services/EmbeddingIndexServiceReconcileTest.php` (new)

**Interfaces:**
- Consumes: existing `IndexSearchableContent::dispatch(string $modelClass, int $id)` and `RemoveFromSearchIndex::dispatch(string $type, int $id)` (unchanged).
- Produces:
  - `EmbeddingIndexService::indexedSourceIds(string $type): array<int, int>`
  - `EmbeddingIndexService::reconcile(): array<string, array{missing: int, orphaned: int}>`
  - Artisan command `search:reconcile-index`.

- [ ] **Step 1: Write the failing test for `indexedSourceIds()` pagination**

Create `tests/Feature/Services/EmbeddingIndexServiceReconcileTest.php`:

```php
<?php

namespace Tests\Feature\Services;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Services\EmbeddingIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmbeddingIndexServiceReconcileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProject(): Project
    {
        return Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);
    }

    public function test_indexed_source_ids_paginates_through_qdrant_scroll(): void
    {
        Http::fake([
            '*/points/scroll' => Http::sequence()
                ->push(['result' => [
                    'points' => [
                        ['payload' => ['source_id' => 1]],
                        ['payload' => ['source_id' => 2]],
                    ],
                    'next_page_offset' => 2,
                ]])
                ->push(['result' => [
                    'points' => [
                        ['payload' => ['source_id' => 3]],
                    ],
                    'next_page_offset' => null,
                ]]),
        ]);

        $ids = app(EmbeddingIndexService::class)->indexedSourceIds('milestone');

        $this->assertSame([1, 2, 3], $ids);
    }

    public function test_reconcile_queues_index_job_for_a_missing_record_and_removal_job_for_an_orphaned_point(): void
    {
        Queue::fake();

        $milestone = $this->makeProject()->milestones()->create(['title' => 'Ship v1']);

        // Reset the fake: the create() above already queued its own index job via
        // the observer, and this test only cares about what reconcile() queues.
        Queue::fake();

        Http::fake([
            '*/collections/project_manager_content' => Http::response(['result' => ['status' => 'green']]),
            '*/points/scroll' => function ($request) {
                $filterType = $request['filter']['must'][0]['match']['value'];

                return match ($filterType) {
                    'milestone' => Http::response(['result' => ['points' => [], 'next_page_offset' => null]]),
                    'technical_debt' => Http::response(['result' => [
                        'points' => [['payload' => ['source_id' => 999]]],
                        'next_page_offset' => null,
                    ]]),
                    default => Http::response(['result' => ['points' => [], 'next_page_offset' => null]]),
                };
            },
        ]);

        app(EmbeddingIndexService::class)->reconcile();

        Queue::assertPushed(IndexSearchableContent::class, fn ($job) => $job->modelClass === Milestone::class && $job->id === $milestone->id);
        Queue::assertPushed(RemoveFromSearchIndex::class, fn ($job) => $job->type === 'technical_debt' && $job->id === 999);
    }

    public function test_reconcile_treats_a_milestone_of_a_soft_deleted_project_as_an_orphan_not_a_missing_record(): void
    {
        Queue::fake();

        $project = $this->makeProject();
        $milestone = $project->milestones()->create(['title' => 'Ship v1']);
        $project->delete();

        Queue::fake();

        Http::fake([
            '*/collections/project_manager_content' => Http::response(['result' => ['status' => 'green']]),
            '*/points/scroll' => function ($request) use ($milestone) {
                $filterType = $request['filter']['must'][0]['match']['value'];

                if ($filterType === 'milestone') {
                    // Qdrant still has a stale point for this milestone.
                    return Http::response(['result' => [
                        'points' => [['payload' => ['source_id' => $milestone->id]]],
                        'next_page_offset' => null,
                    ]]);
                }

                return Http::response(['result' => ['points' => [], 'next_page_offset' => null]]);
            },
        ]);

        app(EmbeddingIndexService::class)->reconcile();

        Queue::assertPushed(RemoveFromSearchIndex::class, fn ($job) => $job->type === 'milestone' && $job->id === $milestone->id);
        Queue::assertNotPushed(IndexSearchableContent::class, fn ($job) => $job->id === $milestone->id);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker compose exec php php artisan test --filter=EmbeddingIndexServiceReconcileTest`
Expected: FAIL — `Call to undefined method App\Services\EmbeddingIndexService::indexedSourceIds()` (and `reconcile()`).

- [ ] **Step 3: Implement `indexedSourceIds()` and `reconcile()`**

In `app/Services/EmbeddingIndexService.php`, add `use App\Jobs\RemoveFromSearchIndex;` to the imports (alongside the existing `use App\Jobs\IndexSearchableContent;` on line 5), then add these two methods after `search()` (after line 135, before the `rebuildAll()` doc comment):

```php
    /**
     * All `source_id`s currently indexed under $type in Qdrant, paginating
     * through the full result set via `next_page_offset`.
     *
     * @return array<int, int>
     */
    public function indexedSourceIds(string $type): array
    {
        $ids = [];
        $offset = null;

        do {
            $body = [
                'limit' => 250,
                'with_payload' => true,
                'with_vector' => false,
                'filter' => [
                    'must' => [
                        ['key' => 'type', 'match' => ['value' => $type]],
                    ],
                ],
            ];

            if ($offset !== null) {
                $body['offset'] = $offset;
            }

            $response = Http::baseUrl($this->qdrantBaseUrl)
                ->post("/collections/{$this->collection}/points/scroll", $body)
                ->throw();

            foreach ($response->json('result.points', []) as $point) {
                $ids[] = (int) $point['payload']['source_id'];
            }

            $offset = $response->json('result.next_page_offset');
        } while ($offset !== null);

        return $ids;
    }

    /**
     * Diffs the database (source of truth) against the Qdrant index per
     * Searchable type and dispatches the jobs needed to close the gap:
     * missing records get (re)indexed, points with no live record behind
     * them get removed. `annotation`, `milestone` and `technical_debt` all
     * depend on a Project/Idea parent that can be soft-deleted — a record
     * whose parent is gone counts as "should not be indexed", not as
     * missing. Safe to run repeatedly; see
     * docs/superpowers/specs/2026-09-05-rag-hardening-design.md.
     *
     * @return array<string, array{missing: int, orphaned: int}>
     */
    public function reconcile(): array
    {
        $this->ensureCollection();

        $expectedIds = [
            'project' => Project::query()->pluck('id')->all(),
            'idea' => Idea::query()->pluck('id')->all(),
            'annotation' => Annotation::whereHasMorph('annotatable', [Project::class, Idea::class])->pluck('id')->all(),
            'milestone' => Milestone::whereHas('project')->pluck('id')->all(),
            'technical_debt' => TechnicalDebt::whereHas('project')->pluck('id')->all(),
        ];

        $modelClasses = [
            'project' => Project::class,
            'idea' => Idea::class,
            'annotation' => Annotation::class,
            'milestone' => Milestone::class,
            'technical_debt' => TechnicalDebt::class,
        ];

        $summary = [];

        foreach ($expectedIds as $type => $ids) {
            $indexed = $this->indexedSourceIds($type);

            $missing = array_diff($ids, $indexed);
            $orphaned = array_diff($indexed, $ids);

            foreach ($missing as $id) {
                IndexSearchableContent::dispatch($modelClasses[$type], $id);
            }

            foreach ($orphaned as $id) {
                RemoveFromSearchIndex::dispatch($type, $id);
            }

            $summary[$type] = ['missing' => count($missing), 'orphaned' => count($orphaned)];
        }

        return $summary;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec php php artisan test --filter=EmbeddingIndexServiceReconcileTest`
Expected: PASS.

- [ ] **Step 5: Create the artisan command**

Create `app/Console/Commands/ReconcileSearchIndexCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Services\EmbeddingIndexService;
use Illuminate\Console\Command;

class ReconcileSearchIndexCommand extends Command
{
    protected $signature = 'search:reconcile-index';

    protected $description = 'Diff the Qdrant search index against the database and queue jobs to close any gap (missing or orphaned points)';

    public function handle(EmbeddingIndexService $index): int
    {
        $this->info('Comparing the search index against the database...');

        $summary = $index->reconcile();

        $this->table(
            ['Type', 'Missing (queued to index)', 'Orphaned (queued to remove)'],
            collect($summary)->map(fn ($counts, $type) => [$type, $counts['missing'], $counts['orphaned']])->all()
        );

        $this->info('Jobs queued. Run a queue worker (php artisan queue:work) to process them.');

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 6: Register the daily schedule**

In `routes/console.php`, add below the existing `use` statements and `inspire` command:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('search:reconcile-index')->daily();
```

- [ ] **Step 7: Add the `scheduler` container so the schedule actually runs**

The project has no cron/scheduler process today — `Schedule::command()` alone is inert without something calling `schedule:run` periodically. Add a new service to `docker-compose.yml`, after the `queue-worker` block:

```yaml
  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: pm_scheduler
    restart: unless-stopped
    working_dir: /var/www
    command: php artisan schedule:work
    volumes:
      - ./:/var/www
    depends_on:
      - qdrant
      - ollama
    networks:
      - pm_network
```

`schedule:work` runs the scheduler loop in the foreground and shells out to a fresh `php artisan search:reconcile-index` process each time it's due — so, unlike `queue:work`, this dispatcher process doesn't accumulate code staleness the way Task 3 worried about for the queue worker.

- [ ] **Step 8: Bring the new container up and verify manually**

Run: `docker compose up -d scheduler`
Run: `docker compose exec php php artisan search:reconcile-index`
Expected: a table showing, per type, how many records were missing/orphaned — given the current known gap, expect `milestone: 44 missing`, `technical_debt: 45 missing`, `idea: 2 missing`, `annotation: 1 missing, 1 orphaned` (the project #10 annotation), `project: 0, 0`.

- [ ] **Step 9: Let the queue worker process the backfill and confirm in Qdrant**

Run: `docker compose logs -f queue-worker` (watch it process ~90 jobs)
Run:
```bash
curl -s -X POST http://localhost:6333/collections/project_manager_content/points/count \
  -H 'Content-Type: application/json' -d '{"filter":{"must":[{"key":"type","match":{"value":"milestone"}}]}}'
```
Expected: count now matches `Milestone::count()` in the database (44, or whatever it is by then), and the previously-orphaned annotation for project #10 is gone from a similar count query for `annotation`.

- [ ] **Step 10: Run the full test suite**

Run: `docker compose exec php php artisan test`
Expected: all tests pass.

- [ ] **Step 11: Commit**

```bash
git add app/Services/EmbeddingIndexService.php app/Console/Commands/ReconcileSearchIndexCommand.php routes/console.php docker-compose.yml tests/Feature/Services/EmbeddingIndexServiceReconcileTest.php
git commit -m "Add RAG index reconciliation: daily self-healing sync between the database and Qdrant (fixes debts #51 and #52)"
```

---

### Task 5: Automatic technical debt on permanent job failure

**Files:**
- Create: `app/Services/FailedIndexingDebtRecorder.php`
- Modify: `app/Jobs/IndexSearchableContent.php` (add `failed()`)
- Modify: `app/Jobs/RemoveFromSearchIndex.php` (add `failed()`)
- Test: `tests/Unit/Services/FailedIndexingDebtRecorderTest.php` (new)
- Test: `tests/Feature/Jobs/JobFailureRecordsTechnicalDebtTest.php` (new)

**Interfaces:**
- Consumes: `TechnicalDebt` via `Project::technicalDebts()` (existing `HasMany`, unchanged).
- Produces: `FailedIndexingDebtRecorder::record(string $key, string $message): void`, called from both jobs' new `failed(\Throwable $exception): void` methods.

- [ ] **Step 1: Write the failing unit test for the recorder**

Create `tests/Unit/Services/FailedIndexingDebtRecorderTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\TechnicalDebt;
use App\Services\FailedIndexingDebtRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FailedIndexingDebtRecorderTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTargetProject(): Project
    {
        return Project::create([
            'name' => 'Gerenciador Projetos',
            'slug' => 'gerenciador-projetos',
            'path' => '/var/www',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);
    }

    public function test_record_creates_a_technical_debt_on_the_gerenciador_projetos_project(): void
    {
        $project = $this->makeTargetProject();

        app(FailedIndexingDebtRecorder::class)->record('index:App\\Models\\Annotation:44', 'Class "annotation" not found');

        $this->assertSame(1, $project->technicalDebts()->count());
        $this->assertStringContainsString('index:App\\Models\\Annotation:44', $project->technicalDebts()->first()->title);
    }

    public function test_record_does_not_duplicate_a_debt_for_the_same_key(): void
    {
        $project = $this->makeTargetProject();
        $recorder = app(FailedIndexingDebtRecorder::class);

        $recorder->record('index:App\\Models\\Annotation:44', 'Class "annotation" not found');
        $recorder->record('index:App\\Models\\Annotation:44', 'Class "annotation" not found');

        $this->assertSame(1, $project->technicalDebts()->count());
    }

    public function test_record_does_nothing_when_the_target_project_does_not_exist(): void
    {
        app(FailedIndexingDebtRecorder::class)->record('index:App\\Models\\Annotation:44', 'boom');

        $this->assertSame(0, TechnicalDebt::count());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec php php artisan test --filter=FailedIndexingDebtRecorderTest`
Expected: FAIL — `Class "App\Services\FailedIndexingDebtRecorder" not found`.

- [ ] **Step 3: Implement the recorder**

Create `app/Services/FailedIndexingDebtRecorder.php`:

```php
<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Str;

class FailedIndexingDebtRecorder
{
    /**
     * Create a technical debt entry the first time a given indexing job
     * (identified by $key, e.g. "index:App\Models\Annotation:44") fails
     * permanently. Later failures of the same job are silent no-ops so a
     * persistent problem doesn't flood the board with duplicates.
     */
    public function record(string $key, string $message): void
    {
        $project = Project::where('slug', 'gerenciador-projetos')->first();

        if (! $project) {
            return;
        }

        $prefix = "Job de indexação RAG falhou ({$key})";

        if ($project->technicalDebts()->where('title', 'like', "{$prefix}%")->exists()) {
            return;
        }

        $project->technicalDebts()->create([
            'title' => "{$prefix}: ".Str::limit($message, 150),
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec php php artisan test --filter=FailedIndexingDebtRecorderTest`
Expected: PASS.

- [ ] **Step 5: Commit the recorder**

```bash
git add app/Services/FailedIndexingDebtRecorder.php tests/Unit/Services/FailedIndexingDebtRecorderTest.php
git commit -m "Add FailedIndexingDebtRecorder: turns a permanently-failed indexing job into a visible technical debt"
```

- [ ] **Step 6: Write the failing feature test for both jobs' `failed()` hooks**

Create `tests/Feature/Jobs/JobFailureRecordsTechnicalDebtTest.php`:

```php
<?php

namespace Tests\Feature\Jobs;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Annotation;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFailureRecordsTechnicalDebtTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTargetProject(): Project
    {
        return Project::create([
            'name' => 'Gerenciador Projetos',
            'slug' => 'gerenciador-projetos',
            'path' => '/var/www',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);
    }

    public function test_index_searchable_content_failed_hook_creates_a_technical_debt(): void
    {
        $project = $this->makeTargetProject();

        (new IndexSearchableContent(Annotation::class, 44))->failed(new \RuntimeException('Class "annotation" not found'));

        $this->assertSame(1, $project->technicalDebts()->count());
        $this->assertStringContainsString('index:App\Models\Annotation:44', $project->technicalDebts()->first()->title);
    }

    public function test_remove_from_search_index_failed_hook_creates_a_technical_debt(): void
    {
        $project = $this->makeTargetProject();

        (new RemoveFromSearchIndex('annotation', 3))->failed(new \RuntimeException('Qdrant unreachable'));

        $this->assertSame(1, $project->technicalDebts()->count());
        $this->assertStringContainsString('remove:annotation:3', $project->technicalDebts()->first()->title);
    }
}
```

- [ ] **Step 7: Run test to verify it fails**

Run: `docker compose exec php php artisan test --filter=JobFailureRecordsTechnicalDebtTest`
Expected: FAIL — `Call to undefined method App\Jobs\IndexSearchableContent::failed()` (and likewise for `RemoveFromSearchIndex`).

- [ ] **Step 8: Add `failed()` to both jobs**

In `app/Jobs/IndexSearchableContent.php`, add the import `use App\Services\FailedIndexingDebtRecorder;` and this method after `handle()` (after line 39):

```php
    public function failed(\Throwable $exception): void
    {
        app(FailedIndexingDebtRecorder::class)->record(
            "index:{$this->modelClass}:{$this->id}",
            $exception->getMessage(),
        );
    }
```

In `app/Jobs/RemoveFromSearchIndex.php`, add the same import and this method after `handle()` (after line 23):

```php
    public function failed(\Throwable $exception): void
    {
        app(FailedIndexingDebtRecorder::class)->record(
            "remove:{$this->type}:{$this->id}",
            $exception->getMessage(),
        );
    }
```

- [ ] **Step 9: Run test to verify it passes**

Run: `docker compose exec php php artisan test --filter=JobFailureRecordsTechnicalDebtTest`
Expected: PASS.

- [ ] **Step 10: Run the full test suite**

Run: `docker compose exec php php artisan test`
Expected: all tests pass.

- [ ] **Step 11: Commit**

```bash
git add app/Jobs/IndexSearchableContent.php app/Jobs/RemoveFromSearchIndex.php tests/Feature/Jobs/JobFailureRecordsTechnicalDebtTest.php
git commit -m "Record a technical debt automatically when an indexing job fails permanently"
```

- [ ] **Step 12: Clear the one pre-existing failed job and verify the new pipeline (manual, not a test)**

Run: `docker compose exec php php artisan queue:failed` — confirm the known "Class \"annotation\" not found" entry is still there.
Run: `docker compose exec php php artisan queue:flush` — clears old failed jobs (safe: reconciliation from Task 4 already redispatched a fresh job for annotation #44, so nothing is lost).
Run: `docker compose exec php php artisan search:reconcile-index` once more to confirm `annotation: 0 missing, 0 orphaned` at this point.

---

## After all tasks

Run the full suite once more end to end (`docker compose exec php php artisan test`) and re-run `docker compose exec php php artisan search:reconcile-index` — expect every type to report `0 missing, 0 orphaned`. At that point débitos #51, #52 and #53 can be marked resolved in the project board.
