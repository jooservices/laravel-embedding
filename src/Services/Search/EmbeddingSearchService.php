<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Search;

use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingSearch;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;

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
        $resultLimit = isset($filters['min_score']) ? max($limit * 3, $limit) : $limit;
        $results = $this->repository->searchSimilar($vector, $resultLimit, $filters);

        if (! isset($filters['min_score']) || ! is_numeric($filters['min_score'])) {
            return $results->take($limit)->values();
        }

        $minScore = (float) $filters['min_score'];

        return $results
            ->filter(static fn (mixed $item): bool => $item instanceof StoredEmbeddingData && $item->score() !== null && $item->score() >= $minScore)
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
