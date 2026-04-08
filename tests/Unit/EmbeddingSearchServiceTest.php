<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Services\Search\EmbeddingSearchService;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;

final class EmbeddingSearchServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_similar_to_text_embeds_query_and_delegates_to_repository(): void
    {
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $vector = EmbeddingVectorData::make(
            ChunkData::make('hello', 0, 0, 5),
            [0.1, 0.2],
            'ollama',
            'nomic-embed-text',
        );

        $provider->shouldReceive('embed')->once()->andReturn($vector);
        $repository->shouldReceive('searchSimilar')
            ->once()
            ->with([0.1, 0.2], 3, ['namespace' => 'docs'])
            ->andReturn(collect());

        $service = new EmbeddingSearchService($provider, $repository);
        $results = $service->similarToText('hello', 3, ['namespace' => 'docs']);

        $this->assertCount(0, $results);
    }
}
