<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\ProjectManagerServer;
use App\Mcp\Tools\GetIdeaTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\ListIdeasTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTagsTool;
use App\Models\Idea;
use App\Models\IdeaPriority;
use App\Models\IdeaStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReadToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests only exercise read tools; fake the queue so the
        // Project/Idea/Annotation observers don't dispatch real search
        // indexing jobs (QUEUE_CONNECTION=sync in the test environment).
        Queue::fake();
    }

    public function test_list_projects_returns_projects_filtered_by_status(): void
    {
        Project::create([
            'name' => 'Alpha',
            'slug' => 'alpha',
            'path' => '/tmp/alpha',
            'description' => 'First project',
            'status_id' => ProjectStatus::where('code', 'in-progress')->value('id'),
            'progress' => 40,
        ]);

        Project::create([
            'name' => 'Beta',
            'slug' => 'beta',
            'path' => '/tmp/beta',
            'description' => 'Second project',
            'status_id' => ProjectStatus::where('code', 'done')->value('id'),
            'progress' => 100,
        ]);

        ProjectManagerServer::tool(ListProjectsTool::class, ['status' => 'in-progress'])
            ->assertOk()
            ->assertSee('Alpha')
            ->assertDontSee('Beta');
    }

    public function test_get_project_returns_full_details_by_slug(): void
    {
        $project = Project::create([
            'name' => 'Gamma',
            'slug' => 'gamma',
            'path' => '/tmp/gamma',
            'description' => 'Third project',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
            'progress' => 10,
        ]);

        $project->annotations()->create(['title' => 'Note', 'content' => 'Some content', 'pinned' => true]);

        ProjectManagerServer::tool(GetProjectTool::class, ['slug' => 'gamma'])
            ->assertOk()
            ->assertSee('Gamma')
            ->assertSee('Some content');
    }

    public function test_get_project_errors_when_slug_not_found(): void
    {
        ProjectManagerServer::tool(GetProjectTool::class, ['slug' => 'missing'])
            ->assertHasErrors();
    }

    public function test_list_ideas_returns_ideas_filtered_by_status(): void
    {
        Idea::create([
            'title' => 'Idea One',
            'description' => 'Desc',
            'status_id' => IdeaStatus::where('code', 'raw')->value('id'),
            'priority_id' => IdeaPriority::where('code', 'medium')->value('id'),
        ]);

        ProjectManagerServer::tool(ListIdeasTool::class, ['status' => 'raw'])
            ->assertOk()
            ->assertSee('Idea One');
    }

    public function test_get_idea_returns_full_details_by_id(): void
    {
        $idea = Idea::create([
            'title' => 'Idea Two',
            'description' => 'Desc',
            'content' => 'Long form content',
            'status_id' => IdeaStatus::where('code', 'raw')->value('id'),
            'priority_id' => IdeaPriority::where('code', 'medium')->value('id'),
        ]);

        ProjectManagerServer::tool(GetIdeaTool::class, ['id' => $idea->id])
            ->assertOk()
            ->assertSee('Long form content');
    }

    public function test_list_tags_returns_all_tags(): void
    {
        Tag::create(['name' => 'backend', 'color' => '#000']);

        ProjectManagerServer::tool(ListTagsTool::class)
            ->assertOk()
            ->assertSee('backend');
    }
}
