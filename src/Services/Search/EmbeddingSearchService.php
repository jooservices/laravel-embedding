<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Search;

use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingSearch;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;

final class EmbeddingSearchService implements EmbeddingSearch
{
    public function __construct(
        private readonly EmbeddingProvider $provider,
        private readonly EmbeddingRepository $repository,
    ) {}

    public function similarToText(string $text, int $limit = 5, array $filters = []): \Illuminate\Support\Collection
    {
        $vector = $this->provider->embed(
            ChunkData::make(
                content: $text,
                index: 0,
                startOffset: 0,
                endOffset: mb_strlen($text),
            ),
        );

        return $this->similarToVector($vector->vector, $limit, $filters);
    }

    public function similarToVector(array $vector, int $limit = 5, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->repository->searchSimilar($vector, $limit, $filters)
            ->take($limit)
            ->values();
    }

    public function similarToTextInNamespace(string $text, string $namespace, int $limit = 5, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->similarToText($text, $limit, [...$filters, 'namespace' => $namespace]);
    }

    public function similarToVectorInNamespace(array $vector, string $namespace, int $limit = 5, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->similarToVector($vector, $limit, [...$filters, 'namespace' => $namespace]);
    }

    public function similarToTextAboveScore(string $text, float $minScore, int $limit = 5, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->similarToText($text, $limit, [...$filters, 'min_score' => $minScore]);
    }

    public function similarToVectorAboveScore(array $vector, float $minScore, int $limit = 5, array $filters = []): \Illuminate\Support\Collection
    {
        return $this->similarToVector($vector, $limit, [...$filters, 'min_score' => $minScore]);
    }
}
