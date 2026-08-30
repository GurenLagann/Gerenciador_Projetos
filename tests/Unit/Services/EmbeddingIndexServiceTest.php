<?php

namespace Tests\Unit\Services;

use App\Services\EmbeddingIndexService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingIndexServiceTest extends TestCase
{
    public function test_upsert_point_embeds_text_and_upserts_a_deterministic_point_id(): void
    {
        Http::fake([
            '*/api/embed' => Http::response(['embeddings' => [array_fill(0, 1024, 0.1)]]),
            '*/points' => Http::response(['status' => 'ok']),
        ]);

        app(EmbeddingIndexService::class)->upsertPoint('idea', 42, 'My Idea', 'Some idea content');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/embed')
                && $request['model'] === config('services.ollama.embed_model')
                && str_contains($request['input'], 'My Idea');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/points')
                && $request['points'][0]['id'] === 2_000_000_042
                && $request['points'][0]['payload']['type'] === 'idea';
        });
    }

    public function test_upsert_point_removes_instead_of_embedding_when_text_is_empty(): void
    {
        Http::fake([
            '*/points/delete' => Http::response(['status' => 'ok']),
        ]);

        app(EmbeddingIndexService::class)->upsertPoint('project', 7, 'Empty Project', '');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/api/embed'));
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/points/delete')
                && $request['points'][0] === 1_000_000_007;
        });
    }

    public function test_search_embeds_the_query_and_maps_qdrant_results(): void
    {
        Http::fake([
            '*/api/embed' => Http::response(['embeddings' => [array_fill(0, 1024, 0.1)]]),
            '*/points/search' => Http::response(['result' => [
                ['score' => 0.9, 'payload' => ['type' => 'annotation', 'source_id' => 1, 'title' => 'Note', 'text' => 'Long content here']],
            ]]),
        ]);

        $results = app(EmbeddingIndexService::class)->search('some query', ['annotation'], 5);

        $this->assertSame('annotation', $results[0]['type']);
        $this->assertSame(1, $results[0]['source_id']);
        $this->assertSame(0.9, $results[0]['score']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/points/search')
                && $request['filter']['must'][0]['match']['any'] === ['annotation'];
        });
    }
}
