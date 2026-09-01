<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\ProjectManagerServer;
use App\Mcp\Tools\SearchNotesTool;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchNotesToolTest extends TestCase
{
    public function test_it_returns_semantic_search_results(): void
    {
        Http::fake([
            '*/api/embed' => Http::response(['embeddings' => [array_fill(0, 1024, 0.1)]]),
            '*/points/search' => Http::response(['result' => [
                ['score' => 0.87, 'payload' => ['type' => 'idea', 'source_id' => 3, 'title' => 'App de reservas', 'text' => 'Conteúdo relevante da ideia']],
            ]]),
        ]);

        ProjectManagerServer::tool(SearchNotesTool::class, ['query' => 'agendar horário com terapeuta'])
            ->assertOk()
            ->assertSee('App de reservas');
    }

    public function test_it_requires_a_query(): void
    {
        ProjectManagerServer::tool(SearchNotesTool::class, [])
            ->assertHasErrors();
    }

    public function test_it_accepts_milestone_and_technical_debt_as_valid_types(): void
    {
        Http::fake([
            '*/api/embed' => Http::response(['embeddings' => [array_fill(0, 1024, 0.1)]]),
            '*/points/search' => Http::response(['result' => []]),
        ]);

        ProjectManagerServer::tool(SearchNotesTool::class, [
            'query' => 'refactor',
            'types' => ['milestone', 'technical_debt'],
        ])->assertOk();
    }
}
