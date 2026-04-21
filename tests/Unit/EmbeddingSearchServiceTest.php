<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Support\Collection;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;
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

    public function test_similar_to_vector_above_score_delegates_threshold_to_repository(): void
    {
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $repository->shouldReceive('searchSimilar')
            ->once()
            ->with([0.1, 0.2], 2, ['min_score' => 0.8])
            ->andReturn(new Collection([
                $this->makeStoredResult(1, 0.05),
                $this->makeStoredResult(2, 0.18),
            ]));

        $service = new EmbeddingSearchService($provider, $repository);
        $results = $service->similarToVectorAboveScore([0.1, 0.2], 0.8, 2);

        $this->assertCount(2, $results);
        $this->assertSame([1, 2], $results->pluck('id')->all());
    }

    public function test_namespace_helpers_forward_namespace_filters(): void
    {
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $embedded = EmbeddingVectorData::make(
            ChunkData::make('search me', 0, 0, 9),
            [0.3, 0.4],
            'ollama',
            'nomic-embed-text',
        );

        $provider->shouldReceive('embed')->once()->andReturn($embedded);
        $repository->shouldReceive('searchSimilar')
            ->once()
            ->with([0.3, 0.4], 4, ['namespace' => 'docs', 'target_type' => 'document'])
            ->andReturn(collect([$this->makeStoredResult(5, 0.1)]));
        $repository->shouldReceive('searchSimilar')
            ->once()
            ->with([0.3, 0.4], 4, ['namespace' => 'docs'])
            ->andReturn(collect([$this->makeStoredResult(6, 0.2)]));

        $service = new EmbeddingSearchService($provider, $repository);

        $textResults = $service->similarToTextInNamespace('search me', 'docs', 4, ['target_type' => 'document']);
        $vectorResults = $service->similarToVectorInNamespace([0.3, 0.4], 'docs', 4);

        $this->assertSame([5], $textResults->pluck('id')->all());
        $this->assertSame([6], $vectorResults->pluck('id')->all());
    }

    public function test_similar_to_text_above_score_applies_min_score_filter(): void
    {
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $embedded = EmbeddingVectorData::make(
            ChunkData::make('threshold', 0, 0, 9),
            [0.9, 0.8],
            'ollama',
            'nomic-embed-text',
        );

        $provider->shouldReceive('embed')->once()->andReturn($embedded);
        $repository->shouldReceive('searchSimilar')
            ->once()
            ->with([0.9, 0.8], 1, ['min_score' => 0.95])
            ->andReturn(collect([$this->makeStoredResult(9, 0.01)]));

        $service = new EmbeddingSearchService($provider, $repository);
        $results = $service->similarToTextAboveScore('threshold', 0.95, 1);

        $this->assertSame([9], $results->pluck('id')->all());
    }

    private function makeStoredResult(int $id, ?float $distance): StoredEmbeddingData
    {
        $vector = EmbeddingVectorData::make(
            ChunkData::make("chunk {$id}", $id, 0, 7),
            [0.1, 0.2],
            'ollama',
            'nomic-embed-text',
        );

        return new StoredEmbeddingData(
            id: $id,
            vector: $vector,
            targetType: 'document',
            targetId: (string) $id,
            embeddableType: null,
            embeddableId: null,
            namespace: 'docs',
            meta: [],
            distance: $distance,
            createdAt: now(),
            updatedAt: now(),
        );
    }
}
