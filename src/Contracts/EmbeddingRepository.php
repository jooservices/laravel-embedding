<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
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
    public function store(EmbeddingVectorData $vector, Model|EmbeddingTargetData|null $target = null, array $meta = []): StoredEmbeddingData;

    /**
     * Persist a batch of embedding vectors.
     *
     * @param  EmbeddingVectorData[]  $vectors
     * @param  array<string, mixed>  $meta
     * @return StoredEmbeddingData[]
     */
    public function storeBatch(array $vectors, Model|EmbeddingTargetData|null $target = null, array $meta = []): array;

    /**
     * Persist a staged vector that should not become searchable until activated.
     *
     * @param  array<string, mixed>  $meta
     */
    public function stage(EmbeddingVectorData $vector, Model|EmbeddingTargetData|null $target, array $meta, string $batchToken): StoredEmbeddingData;

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @param  array<string, mixed>  $meta
     * @return StoredEmbeddingData[]
     */
    public function stageBatch(array $vectors, Model|EmbeddingTargetData|null $target, array $meta, string $batchToken): array;

    /**
     * Replace all stored embeddings for the given target with the provided set.
     *
     * @param  EmbeddingVectorData[]  $vectors
     * @param  array<string, mixed>  $meta
     * @return StoredEmbeddingData[]
     */
    public function replaceForTarget(array $vectors, Model|EmbeddingTargetData $target, array $meta = []): array;

    /**
     * Activate a staged batch and discard previously active records for the same target.
     */
    public function activateStagedBatch(Model|EmbeddingTargetData $target, string $batchToken): int;

    /**
     * Delete inactive staged rows for the given target and token.
     */
    public function deleteStagedBatch(Model|EmbeddingTargetData $target, string $batchToken): int;

    /**
     * Delete all embedding records associated with a given target model.
     */
    public function deleteForTarget(Model|EmbeddingTargetData $target): int;

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, StoredEmbeddingData>
     */
    public function findForTarget(Model|EmbeddingTargetData $target, array $filters = []): \Illuminate\Support\Collection;

    /**
     * @param  string[]  $contentHashes
     */
    public function hasMatchingContentHashes(
        Model|EmbeddingTargetData $target,
        array $contentHashes,
        string $provider,
        string $model,
    ): bool;

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
    public function searchSimilar(array $embedding, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;
}
