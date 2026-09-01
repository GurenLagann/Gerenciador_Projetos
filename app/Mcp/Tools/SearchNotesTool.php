<?php

namespace App\Mcp\Tools;

use App\Services\EmbeddingIndexService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Semantic search over project descriptions, idea descriptions/content, annotation notes, milestone titles/descriptions, and technical debt titles. Returns pointers and snippets, not full bodies — follow up with get-project-tool/get-idea-tool for the complete record.')]
#[IsReadOnly]
class SearchNotesTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->validate([
            'query' => 'required|string',
            'types' => 'sometimes|array',
            'types.*' => 'in:project,idea,annotation,milestone,technical_debt',
            'limit' => 'sometimes|integer|min:1|max:25',
        ]);

        $index = app(EmbeddingIndexService::class);

        $results = $index->search(
            query: $request->get('query'),
            types: $request->get('types'),
            limit: (int) $request->get('limit', 10),
        );

        return Response::structured(['results' => $results]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Free-text search query.')->required(),
            'types' => $schema->array()
                ->items($schema->string()->enum(['project', 'idea', 'annotation', 'milestone', 'technical_debt']))
                ->description('Restrict results to these content types. Omit to search everything.'),
            'limit' => $schema->integer()->description('Maximum number of results. Defaults to 10.'),
        ];
    }
}
