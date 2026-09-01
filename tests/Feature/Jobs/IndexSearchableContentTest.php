<?php

namespace Tests\Feature\Jobs;

use App\Jobs\IndexSearchableContent;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Services\EmbeddingIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndexSearchableContentTest extends TestCase
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

    public function test_it_indexes_a_milestone_using_its_title_and_description(): void
    {
        Http::fake([
            '*/api/embed' => Http::response(['embeddings' => [array_fill(0, 1024, 0.1)]]),
            '*/points' => Http::response(['status' => 'ok']),
        ]);

        $milestone = $this->makeProject()->milestones()->create([
            'title' => 'Ship v1',
            'description' => 'Get the first version out the door',
        ]);

        (new IndexSearchableContent('milestone', $milestone->id))->handle(app(EmbeddingIndexService::class));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/embed')
                && str_contains($request['input'], 'Ship v1')
                && str_contains($request['input'], 'Get the first version out the door');
        });
        Http::assertSent(fn ($request) => str_contains($request->url(), '/points') && $request['points'][0]['id'] === 4_000_000_000 + $milestone->id);
    }

    public function test_it_indexes_a_technical_debt_using_only_its_title(): void
    {
        Http::fake([
            '*/api/embed' => Http::response(['embeddings' => [array_fill(0, 1024, 0.1)]]),
            '*/points' => Http::response(['status' => 'ok']),
        ]);

        $debt = $this->makeProject()->technicalDebts()->create(['title' => 'Refactor the scanner']);

        (new IndexSearchableContent('technical_debt', $debt->id))->handle(app(EmbeddingIndexService::class));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/embed')
                && str_contains($request['input'], 'Refactor the scanner');
        });
        Http::assertSent(fn ($request) => str_contains($request->url(), '/points') && $request['points'][0]['id'] === 5_000_000_000 + $debt->id);
    }

    public function test_it_does_nothing_when_the_milestone_no_longer_exists(): void
    {
        Http::fake();

        (new IndexSearchableContent('milestone', 999))->handle(app(EmbeddingIndexService::class));

        Http::assertNothingSent();
    }
}
