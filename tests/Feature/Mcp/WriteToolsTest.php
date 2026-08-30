<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\ProjectManagerServer;
use App\Mcp\Tools\ConvertIdeaTool;
use App\Mcp\Tools\CreateAnnotationTool;
use App\Mcp\Tools\CreateIdeaTool;
use App\Mcp\Tools\CreateMilestoneTool;
use App\Mcp\Tools\CreateTechnicalDebtTool;
use App\Mcp\Tools\PinAnnotationTool;
use App\Mcp\Tools\ToggleMilestoneTool;
use App\Mcp\Tools\UpdateProjectProgressTool;
use App\Mcp\Tools\UpdateProjectStatusTool;
use App\Models\Idea;
use App\Models\IdeaPriority;
use App\Models\IdeaStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WriteToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    protected function createProject(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
            'progress' => 0,
        ], $overrides));
    }

    public function test_update_project_status_changes_status(): void
    {
        $project = $this->createProject();

        ProjectManagerServer::tool(UpdateProjectStatusTool::class, ['slug' => 'alpha', 'status' => 'done'])
            ->assertOk();

        $this->assertSame('done', $project->fresh()->status->code);
    }

    public function test_update_project_progress_changes_progress(): void
    {
        $project = $this->createProject();

        ProjectManagerServer::tool(UpdateProjectProgressTool::class, ['slug' => 'alpha', 'progress' => 75])
            ->assertOk();

        $this->assertSame(75, $project->fresh()->progress);
    }

    public function test_create_annotation_attaches_note_to_project(): void
    {
        $project = $this->createProject();

        ProjectManagerServer::tool(CreateAnnotationTool::class, [
            'annotatable_type' => 'project',
            'annotatable_id' => $project->id,
            'content' => 'Some note',
        ])->assertOk();

        $this->assertSame(1, $project->annotations()->count());
    }

    public function test_pin_annotation_toggles_pinned(): void
    {
        $project = $this->createProject();
        $annotation = $project->annotations()->create(['content' => 'Note']);

        ProjectManagerServer::tool(PinAnnotationTool::class, ['id' => $annotation->id])->assertOk();

        $this->assertTrue($annotation->fresh()->pinned);
    }

    public function test_create_milestone_appends_with_next_order(): void
    {
        $project = $this->createProject();
        $project->milestones()->create(['title' => 'First', 'order' => 0]);

        ProjectManagerServer::tool(CreateMilestoneTool::class, [
            'project_slug' => 'alpha',
            'title' => 'Second',
        ])->assertOk();

        $this->assertSame(2, $project->milestones()->count());
        $this->assertSame(1, $project->milestones()->where('title', 'Second')->value('order'));
    }

    public function test_create_technical_debt_appends_with_next_order(): void
    {
        $project = $this->createProject();
        $project->technicalDebts()->create(['title' => 'First', 'order' => 0]);

        ProjectManagerServer::tool(CreateTechnicalDebtTool::class, [
            'project_slug' => 'alpha',
            'title' => 'Second',
        ])->assertOk();

        $this->assertSame(2, $project->technicalDebts()->count());
        $this->assertSame(1, $project->technicalDebts()->where('title', 'Second')->value('order'));
    }

    public function test_toggle_milestone_flips_completed_state(): void
    {
        $project = $this->createProject();
        $milestone = $project->milestones()->create(['title' => 'M1', 'order' => 0]);

        ProjectManagerServer::tool(ToggleMilestoneTool::class, ['id' => $milestone->id])->assertOk();

        $this->assertTrue($milestone->fresh()->completed);
    }

    public function test_create_idea_defaults_status_and_priority(): void
    {
        ProjectManagerServer::tool(CreateIdeaTool::class, ['title' => 'New Idea'])->assertOk();

        $idea = Idea::first();
        $this->assertSame('raw', $idea->status->code);
        $this->assertSame('medium', $idea->priority->code);
    }

    public function test_convert_idea_creates_project_and_marks_idea_converted(): void
    {
        $idea = Idea::create([
            'title' => 'Great Idea',
            'description' => 'Desc',
            'status_id' => IdeaStatus::where('code', 'raw')->value('id'),
            'priority_id' => IdeaPriority::where('code', 'medium')->value('id'),
        ]);

        ProjectManagerServer::tool(ConvertIdeaTool::class, ['id' => $idea->id])
            ->assertOk()
            ->assertSee('great-idea');

        $this->assertSame('converted', $idea->fresh()->status->code);
        $this->assertNotNull($idea->fresh()->converted_to_project_id);
        $this->assertDatabaseHas('projects', ['slug' => 'great-idea']);
    }
}
