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

    public function test_record_collapses_a_cascade_of_different_keys_sharing_the_same_message(): void
    {
        $project = $this->makeTargetProject();
        $recorder = app(FailedIndexingDebtRecorder::class);

        $recorder->record('index:App\\Models\\TechnicalDebt:100', 'Connection refused');
        $recorder->record('index:App\\Models\\TechnicalDebt:101', 'Connection refused');
        $recorder->record('index:App\\Models\\TechnicalDebt:102', 'Connection refused');

        $this->assertSame(1, $project->technicalDebts()->count());
    }

    public function test_record_creates_a_new_debt_when_the_previous_one_for_the_same_message_was_resolved(): void
    {
        $project = $this->makeTargetProject();
        $recorder = app(FailedIndexingDebtRecorder::class);

        $recorder->record('index:App\\Models\\Annotation:44', 'Class "annotation" not found');
        $project->technicalDebts()->first()->update(['resolved' => true, 'resolved_at' => now()]);

        $recorder->record('index:App\\Models\\Annotation:44', 'Class "annotation" not found');

        $this->assertSame(2, $project->technicalDebts()->count());
    }

    public function test_record_does_not_treat_a_wildcard_character_in_the_message_as_a_sql_wildcard(): void
    {
        $project = $this->makeTargetProject();
        $recorder = app(FailedIndexingDebtRecorder::class);

        // The "_" below is a SQL LIKE single-character wildcard. Without
        // escaping, the second call's title would accidentally match the
        // first debt's title via LIKE and be treated as a duplicate, even
        // though the two messages are genuinely different.
        $recorder->record('index:App\\Models\\Annotation:1', 'Xyo error');
        $recorder->record('index:App\\Models\\Annotation:2', 'X_o error');

        $this->assertSame(2, $project->technicalDebts()->count());
    }
}
