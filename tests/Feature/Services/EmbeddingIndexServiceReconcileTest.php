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
