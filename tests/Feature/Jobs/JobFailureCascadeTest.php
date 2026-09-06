<?php

namespace Tests\Feature\Jobs;

use App\Jobs\IndexSearchableContent;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\TechnicalDebt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFailureCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_auto_created_technical_debt_that_also_fails_to_index_does_not_mint_a_second_debt(): void
    {
        $project = Project::create([
            'name' => 'Gerenciador Projetos',
            'slug' => 'gerenciador-projetos',
            'path' => '/var/www',
            'status_id' => ProjectStatus::where('code', 'planning')->value('id'),
        ]);

        // First failure: an annotation's indexing job fails, auto-creating a debt.
        (new IndexSearchableContent(\App\Models\Annotation::class, 44))->failed(new \RuntimeException('Connection refused'));
        $this->assertSame(1, $project->technicalDebts()->count());
        $autoDebt = $project->technicalDebts()->first();

        // Second failure: indexing THAT auto-created debt also fails (simulating the
        // outage still being ongoing) — this must NOT create a second debt.
        (new IndexSearchableContent(TechnicalDebt::class, $autoDebt->id))->failed(new \RuntimeException('Connection refused'));

        $this->assertSame(1, $project->fresh()->technicalDebts()->count());
    }
}
