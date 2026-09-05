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
