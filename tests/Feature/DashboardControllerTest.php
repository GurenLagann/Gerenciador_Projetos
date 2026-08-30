<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\IdeaStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    protected function createProject(array $overrides = []): Project
    {
        $slug = $overrides['slug'] ?? 'alpha';

        return Project::create(array_merge([
            'name' => 'Alpha',
            'slug' => $slug,
            'path' => "/tmp/{$slug}",
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
            'progress' => 0,
        ], $overrides));
    }

    public function test_dashboard_loads_successfully(): void
    {
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_shows_open_technical_debt_count(): void
    {
        $project = $this->createProject();
        $project->technicalDebts()->create(['title' => 'Open one', 'order' => 0]);
        $project->technicalDebts()->create(['title' => 'Open two', 'order' => 1]);
        $project->technicalDebts()->create(['title' => 'Resolved', 'order' => 2, 'resolved' => true, 'resolved_at' => now()]);

        $this->get(route('dashboard'))->assertViewHas('openDebtCount', 2);
    }

    public function test_dashboard_shows_top_debt_projects_ordered_by_count(): void
    {
        $busy = $this->createProject(['name' => 'Busy', 'slug' => 'busy']);
        $busy->technicalDebts()->create(['title' => 'A', 'order' => 0]);
        $busy->technicalDebts()->create(['title' => 'B', 'order' => 1]);

        $quiet = $this->createProject(['name' => 'Quiet', 'slug' => 'quiet']);
        $quiet->technicalDebts()->create(['title' => 'C', 'order' => 0]);

        $response = $this->get(route('dashboard'));

        $response->assertViewHas('topDebtProjects', function ($projects) use ($busy) {
            return $projects->first()->id === $busy->id && $projects->first()->open_debt_count === 2;
        });
    }

    public function test_dashboard_shows_idea_pipeline_counts(): void
    {
        Idea::create(['title' => 'Raw idea', 'status_id' => IdeaStatus::where('code', 'raw')->value('id')]);
        Idea::create(['title' => 'Exploring idea', 'status_id' => IdeaStatus::where('code', 'exploring')->value('id')]);
        Idea::create(['title' => 'Another raw idea', 'status_id' => IdeaStatus::where('code', 'raw')->value('id')]);

        $response = $this->get(route('dashboard'));

        $response->assertViewHas('ideaStatusCounts', function ($counts) {
            return $counts->get('raw')->count === 2 && $counts->get('exploring')->count === 1;
        });
    }

    public function test_dashboard_shows_upcoming_milestones_ordered_by_due_date_and_excludes_completed(): void
    {
        $project = $this->createProject();
        $project->milestones()->create(['title' => 'Far out', 'due_date' => now()->addDays(10), 'order' => 0]);
        $overdue = $project->milestones()->create(['title' => 'Overdue', 'due_date' => now()->subDays(2), 'order' => 1]);
        $project->milestones()->create(['title' => 'Done already', 'due_date' => now()->addDays(1), 'completed' => true, 'completed_at' => now(), 'order' => 2]);

        $response = $this->get(route('dashboard'));

        $response->assertViewHas('upcomingMilestones', function ($milestones) use ($overdue) {
            return $milestones->count() === 2 && $milestones->first()->id === $overdue->id;
        });
    }
}
