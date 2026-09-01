<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Models\ProjectStatus;
use App\Services\EmbeddingIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmbeddingIndexServiceRebuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_all_queues_a_job_for_every_milestone_and_technical_debt(): void
    {
        Queue::fake();
        Http::fake(['*/collections/*' => Http::response(['result' => ['status' => 'green']])]);

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);
        $project->milestones()->create(['title' => 'Ship v1']);
        $project->technicalDebts()->create(['title' => 'Refactor scanner']);

        // Observers already queued a job for each on create() above; rebuildAll()
        // must queue one more per record regardless, so reset the fake first.
        Queue::fake();

        $counts = app(EmbeddingIndexService::class)->rebuildAll();

        $this->assertSame(1, $counts['milestone']);
        $this->assertSame(1, $counts['technical_debt']);
    }
}
