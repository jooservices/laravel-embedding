<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;

interface EmbeddingRepository
{
    /**
     * Persist a single embedding vector result to storage.
     *
     * @param  EmbeddingVectorData  $vector  The embedding to persist.
     * @param  Model|null  $target  Optional polymorphic target model.
     * @param  array<string, mixed>  $meta  Arbitrary metadata to store alongside.
     */
    public function store(EmbeddingVectorData $vector, ?Model $target = null, array $meta = []): StoredEmbeddingData;

    /**
     * Persist a batch of embedding vectors.
     *
     * @param  EmbeddingVectorData[]  $vectors
     * @param  array<string, mixed>  $meta
     * @return StoredEmbeddingData[]
     */
    public function storeBatch(array $vectors, ?Model $target = null, array $meta = []): array;

    /**
     * Delete all embedding records associated with a given target model.
     */
    public function deleteForTarget(Model $target): int;

    /**
     * Find a stored embedding by its content hash.
     */
    public function findByHash(string $contentHash): ?StoredEmbeddingData;

    /**
     * Search for embeddings similar to the given vector.
     * Returns a collection of Embedding models ordered by distance.
     *
     * @param  array<float>  $embedding
     */
    public function searchSimilar(array $embedding, int $limit = 5): \Illuminate\Support\Collection;
}
