<?php

namespace Tests\Feature;

use App\Jobs\IndexSearchableContent;
use App\Jobs\RemoveFromSearchIndex;
use App\Models\Idea;
use App\Models\IdeaPriority;
use App\Models\IdeaStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SearchIndexingObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_project_queues_an_index_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        Queue::assertPushed(IndexSearchableContent::class, fn ($job) => $job->type === 'project' && $job->id === $project->id);
    }

    public function test_deleting_a_project_queues_a_removal_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $project->delete();

        Queue::assertPushed(RemoveFromSearchIndex::class, fn ($job) => $job->type === 'project' && $job->id === $project->id);
    }

    public function test_saving_an_idea_queues_an_index_job(): void
    {
        Queue::fake();

        $idea = Idea::create([
            'title' => 'My Idea',
            'status_id' => IdeaStatus::where('code', 'raw')->value('id'),
            'priority_id' => IdeaPriority::where('code', 'medium')->value('id'),
        ]);

        Queue::assertPushed(IndexSearchableContent::class, fn ($job) => $job->type === 'idea' && $job->id === $idea->id);
    }

    public function test_saving_an_annotation_queues_an_index_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $annotation = $project->annotations()->create(['title' => 'Note', 'content' => 'Content']);

        Queue::assertPushed(IndexSearchableContent::class, fn ($job) => $job->type === 'annotation' && $job->id === $annotation->id);
    }

    public function test_saving_a_milestone_queues_an_index_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $milestone = $project->milestones()->create(['title' => 'Ship v1']);

        Queue::assertPushed(IndexSearchableContent::class, fn ($job) => $job->type === 'milestone' && $job->id === $milestone->id);
    }

    public function test_deleting_a_milestone_queues_a_removal_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $milestone = $project->milestones()->create(['title' => 'Ship v1']);
        $milestone->delete();

        Queue::assertPushed(RemoveFromSearchIndex::class, fn ($job) => $job->type === 'milestone' && $job->id === $milestone->id);
    }

    public function test_saving_a_technical_debt_queues_an_index_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $debt = $project->technicalDebts()->create(['title' => 'Refactor scanner']);

        Queue::assertPushed(IndexSearchableContent::class, fn ($job) => $job->type === 'technical_debt' && $job->id === $debt->id);
    }

    public function test_deleting_a_technical_debt_queues_a_removal_job(): void
    {
        Queue::fake();

        $project = Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        $debt = $project->technicalDebts()->create(['title' => 'Refactor scanner']);
        $debt->delete();

        Queue::assertPushed(RemoveFromSearchIndex::class, fn ($job) => $job->type === 'technical_debt' && $job->id === $debt->id);
    }
}
