<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TechnicalDebtControllerTest extends TestCase
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

    public function test_store_creates_technical_debt_with_next_order(): void
    {
        $project = $this->createProject();
        $project->technicalDebts()->create(['title' => 'First', 'order' => 0]);

        $response = $this->post(route('technical-debts.store', $project), ['title' => 'Second']);

        $response->assertRedirect();
        $this->assertSame(2, $project->technicalDebts()->count());
        $this->assertSame(1, $project->technicalDebts()->where('title', 'Second')->value('order'));
    }

    public function test_store_requires_title(): void
    {
        $project = $this->createProject();

        $response = $this->post(route('technical-debts.store', $project), ['title' => '']);

        $response->assertSessionHasErrors('title');
        $this->assertSame(0, $project->technicalDebts()->count());
    }

    public function test_toggle_flips_resolved_state(): void
    {
        $project = $this->createProject();
        $debt = $project->technicalDebts()->create(['title' => 'Refactor legacy code', 'order' => 0]);

        $this->patch(route('technical-debts.toggle', [$project, $debt]))->assertRedirect();
        $debt->refresh();
        $this->assertTrue($debt->resolved);
        $this->assertNotNull($debt->resolved_at);

        $this->patch(route('technical-debts.toggle', [$project, $debt]))->assertRedirect();
        $debt->refresh();
        $this->assertFalse($debt->resolved);
        $this->assertNull($debt->resolved_at);
    }

    public function test_destroy_deletes_technical_debt(): void
    {
        $project = $this->createProject();
        $debt = $project->technicalDebts()->create(['title' => 'Remove dead code', 'order' => 0]);

        $this->delete(route('technical-debts.destroy', [$project, $debt]))->assertRedirect();

        $this->assertSame(0, $project->technicalDebts()->count());
    }
}
