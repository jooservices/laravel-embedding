<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;
use JOOservices\LaravelEmbedding\Models\Embedding;
use JOOservices\LaravelEmbedding\Support\PgvectorSimilarityQuery;
use UnexpectedValueException;

final class EloquentEmbeddingRepository implements EmbeddingRepository
{
    public function store(EmbeddingVectorData $vector, Model|EmbeddingTargetData|null $target = null, array $meta = []): StoredEmbeddingData
    {
        return $this->persist($vector, $target, $meta);
    }

    public function stage(EmbeddingVectorData $vector, Model|EmbeddingTargetData|null $target, array $meta, string $batchToken): StoredEmbeddingData
    {
        return $this->persist($vector, $target, $meta, $batchToken, false);
    }

    public function storeBatch(array $vectors, Model|EmbeddingTargetData|null $target = null, array $meta = []): array
    {
        return $this->persistBatch($vectors, $target, $meta);
    }

    public function stageBatch(array $vectors, Model|EmbeddingTargetData|null $target, array $meta, string $batchToken): array
    {
        return $this->persistBatch($vectors, $target, $meta, $batchToken, false);
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @return StoredEmbeddingData[]
     */
    public function replaceForTarget(array $vectors, Model|EmbeddingTargetData $target, array $meta = []): array
    {
        $connectionName = (new Embedding)->getConnectionName();

        /** @var StoredEmbeddingData[] $stored */
        $stored = DB::connection($connectionName)->transaction(function () use ($vectors, $target, $meta): array {
            $this->deleteForTarget($target);

            return $this->storeBatch($vectors, $target, $meta);
        });

        return $stored;
    }

    public function activateStagedBatch(Model|EmbeddingTargetData $target, string $batchToken): int
    {
        $connectionName = (new Embedding)->getConnectionName();

        return DB::connection($connectionName)->transaction(function () use ($target, $batchToken): int {
            $this->applyTargetFilters(Embedding::query(), $this->normalizeTarget($target))
                ->where(function (Builder $query) use ($batchToken): void {
                    $query->where('is_active', true)
                        ->orWhere(function (Builder $inner) use ($batchToken): void {
                            $inner->where('is_active', false)
                                ->where('batch_token', '!=', $batchToken);
                        });
                })
                ->delete();

            return $this->applyTargetFilters(Embedding::query(), $this->normalizeTarget($target))
                ->where('batch_token', $batchToken)
                ->update(['is_active' => true]);
        });
    }

    public function deleteStagedBatch(Model|EmbeddingTargetData $target, string $batchToken): int
    {
        return $this->applyTargetFilters(Embedding::query(), $this->normalizeTarget($target))
            ->where('is_active', false)
            ->where('batch_token', $batchToken)
            ->delete();
    }

    public function deleteForTarget(Model|EmbeddingTargetData $target): int
    {
        return $this->applyTargetFilters(Embedding::query(), $this->normalizeTarget($target))->delete();
    }

    public function findForTarget(Model|EmbeddingTargetData $target, array $filters = []): Collection
    {
        $query = $this->applyFilters(
            $this->applyTargetFilters(Embedding::query(), $this->normalizeTarget($target)),
            $filters,
        )->orderBy('chunk_index');

        return $query->get()->map(fn (Embedding $record): StoredEmbeddingData => $this->recordToDto($record));
    }

    public function hasMatchingContentHashes(
        Model|EmbeddingTargetData $target,
        array $contentHashes,
        string $provider,
        string $model,
    ): bool {
        $contentHashes = array_values(array_unique($contentHashes));
        if ($contentHashes === []) {
            return false;
        }

        $storedHashes = $this->applyTargetFilters(Embedding::query(), $this->normalizeTarget($target))
            ->where('is_active', true)
            ->where('provider', $provider)
            ->where('model', $model)
            ->orderBy('chunk_index')
            ->get()
            ->pluck('content_hash')
            ->map(static function (mixed $hash): string {
                if (! is_string($hash)) {
                    throw new UnexpectedValueException('Stored content hashes must be strings.');
                }

                return $hash;
            })
            ->all();

        return $storedHashes === $contentHashes;
    }

    public function findByHash(string $contentHash): ?StoredEmbeddingData
    {
        $record = Embedding::query()
            ->active()
            ->where('content_hash', $contentHash)
            ->first();

        if ($record === null) {
            return null;
        }

        // Reconstruct the ChunkData and EmbeddingVectorData from the record
        // so we can return a fully typed DTO without leaking the Eloquent model.
        $chunk = ChunkData::make(
            content: $record->content,
            index: $record->chunk_index,
            startOffset: 0,
            endOffset: mb_strlen($record->content),
        );

        $vector = EmbeddingVectorData::make(
            chunk: $chunk,
            vector: $record->embedding,
            provider: $record->provider,
            model: $record->model,
        );

        return $this->toDto($record, $vector);
    }

    public function searchSimilar(array $embedding, int $limit = 5, array $filters = []): Collection
    {
        return $this->applyFilters(PgvectorSimilarityQuery::apply(
            Embedding::query(),
            $embedding,
            $this->maxDistanceFromFilters($filters),
        ), $filters)
            ->limit($limit)
            ->get()
            ->map(fn (Embedding $record): StoredEmbeddingData => $this->recordToDto($record));
    }

    /**
     * Map an Eloquent Embedding record + vector DTO to a StoredEmbeddingData.
     */
    private function toDto(Embedding $record, EmbeddingVectorData $vector): StoredEmbeddingData
    {
        return new StoredEmbeddingData(
            id: $record->getKey(),
            vector: $vector,
            targetType: $record->target_type,
            targetId: $record->target_id,
            embeddableType: $record->embeddable_type,
            embeddableId: $record->embeddable_id,
            namespace: $record->namespace,
            meta: $record->meta ?? [],
            distance: is_int($record->distance) ? (float) $record->distance : $record->distance,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
        );
    }

    private function recordToDto(Embedding $record): StoredEmbeddingData
    {
        $chunk = ChunkData::make(
            content: $record->content,
            index: $record->chunk_index,
            startOffset: 0,
            endOffset: mb_strlen($record->content),
        );

        $vector = EmbeddingVectorData::make(
            chunk: $chunk,
            vector: $record->embedding,
            provider: $record->provider,
            model: $record->model,
        );

        return $this->toDto($record, $vector);
    }

    private function identityAttributes(EmbeddingVectorData $vector, ?EmbeddingTargetData $target): array
    {
        return [
            'target_type' => $target?->type,
            'target_id' => $target?->id === null ? null : (string) $target->id,
            'namespace' => $target?->namespace,
            'provider' => $vector->provider,
            'model' => $vector->model,
            'chunk_index' => $vector->chunk->index,
            'content_hash' => $vector->chunk->contentHash,
            'batch_token' => null,
        ];
    }

    private function normalizeTarget(Model|EmbeddingTargetData|null $target): ?EmbeddingTargetData
    {
        if ($target instanceof EmbeddingTargetData) {
            return $target;
        }

        if ($target instanceof Model) {
            return EmbeddingTargetData::fromModel($target);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(
        Builder $query,
        array $filters,
    ): Builder {
        if (($filters['include_inactive'] ?? false) !== true) {
            $query->active();
        }

        if (isset($filters['target'])) {
            $query = $this->applyTargetFilters($query, $this->normalizeTarget($filters['target']));
        }

        $query = $this->applyScalarFilters($query, $filters);

        return $this->applyMetaFilters($query, $filters['meta'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyScalarFilters(Builder $query, array $filters): Builder
    {
        $provider = $filters['provider'] ?? null;
        if (is_string($provider)) {
            $query->forProvider($provider);
        }

        $targetType = $filters['target_type'] ?? null;
        if (is_string($targetType)) {
            $query->where('target_type', $targetType);
        }

        $targetId = $filters['target_id'] ?? null;
        if (is_scalar($targetId)) {
            $query->where('target_id', (string) $targetId);
        }

        $model = $filters['model'] ?? null;
        if (is_string($model)) {
            $query->forModel($model);
        }

        $namespace = $filters['namespace'] ?? null;
        if (is_string($namespace)) {
            $query->inNamespace($namespace);
        }

        if (isset($filters['chunk_index']) && is_int($filters['chunk_index'])) {
            $query->where('chunk_index', $filters['chunk_index']);
        }

        return $query;
    }

    private function persist(
        EmbeddingVectorData $vector,
        Model|EmbeddingTargetData|null $target = null,
        array $meta = [],
        ?string $batchToken = null,
        bool $isActive = true,
    ): StoredEmbeddingData {
        $targetData = $this->normalizeTarget($target);

        $record = Embedding::query()->updateOrCreate(
            [...$this->identityAttributes($vector, $targetData), 'batch_token' => $batchToken],
            [
                'embeddable_type' => $target instanceof Model ? $target->getMorphClass() : null,
                'embeddable_id' => $target instanceof Model ? $target->getKey() : null,
                'target_type' => $targetData?->type,
                'target_id' => $targetData?->id === null ? null : (string) $targetData->id,
                'provider' => $vector->provider,
                'model' => $vector->model,
                'dimension' => $vector->dimension,
                'chunk_index' => $vector->chunk->index,
                'content' => $vector->chunk->content,
                'content_hash' => $vector->chunk->contentHash,
                'embedding' => $vector->vector,
                'namespace' => $targetData?->namespace,
                'meta' => empty($meta) ? null : $meta,
                'batch_token' => $batchToken,
                'is_active' => $isActive,
            ],
        );

        return $this->toDto($record, $vector);
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @param  array<string, mixed>  $meta
     * @return StoredEmbeddingData[]
     */
    private function persistBatch(
        array $vectors,
        Model|EmbeddingTargetData|null $target = null,
        array $meta = [],
        ?string $batchToken = null,
        bool $isActive = true,
    ): array {
        if ($vectors === []) {
            return [];
        }

        $connectionName = (new Embedding)->getConnectionName();

        /** @var StoredEmbeddingData[] $stored */
        $stored = DB::connection($connectionName)->transaction(function () use ($vectors, $target, $meta, $batchToken, $isActive): array {
            $targetData = $this->normalizeTarget($target);
            $existing = $this->existingRecordsForVectors($vectors, $targetData, $batchToken);
            $now = now();
            $rowsToInsert = [];
            $table = (new Embedding)->getTable();
            $connection = DB::connection((new Embedding)->getConnectionName());

            foreach ($vectors as $vector) {
                $row = $this->rowAttributes($vector, $target, $targetData, $meta, $batchToken, $isActive, $now);
                $key = $this->identityKey($vector, $targetData, $batchToken);
                $record = $existing->get($key);

                if ($record instanceof Embedding) {
                    $connection->table($table)
                        ->where('id', $record->getKey())
                        ->update($this->batchUpdateAttributes($row));

                    continue;
                }

                $rowsToInsert[] = $row;
            }

            if ($rowsToInsert !== []) {
                $connection->table($table)->insert($rowsToInsert);
            }

            return $this->storedVectorsForBatch($vectors, $targetData, $batchToken);
        });

        return $stored;
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @return Collection<string, Embedding>
     */
    private function existingRecordsForVectors(array $vectors, ?EmbeddingTargetData $target, ?string $batchToken): Collection
    {
        return $this->matchingVectorQuery($vectors, $target, $batchToken)
            ->get()
            ->keyBy(fn (Embedding $record): string => $this->recordIdentityKey($record));
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @return StoredEmbeddingData[]
     */
    private function storedVectorsForBatch(array $vectors, ?EmbeddingTargetData $target, ?string $batchToken): array
    {
        $records = $this->existingRecordsForVectors($vectors, $target, $batchToken);

        return array_map(
            function (EmbeddingVectorData $vector) use ($records, $target, $batchToken): StoredEmbeddingData {
                $record = $records->get($this->identityKey($vector, $target, $batchToken));

                if (! $record instanceof Embedding) {
                    throw new UnexpectedValueException('Persisted embedding record could not be reloaded.');
                }

                return $this->recordToDto($record);
            },
            $vectors,
        );
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     */
    private function matchingVectorQuery(array $vectors, ?EmbeddingTargetData $target, ?string $batchToken): Builder
    {
        return $this->applyBatchIdentityFilters(Embedding::query(), $target, $batchToken)
            ->where(function (Builder $query) use ($vectors): void {
                foreach ($vectors as $vector) {
                    $query->orWhere(function (Builder $itemQuery) use ($vector): void {
                        $itemQuery
                            ->where('provider', $vector->provider)
                            ->where('model', $vector->model)
                            ->where('chunk_index', $vector->chunk->index)
                            ->where('content_hash', $vector->chunk->contentHash);
                    });
                }
            });
    }

    private function applyBatchIdentityFilters(Builder $query, ?EmbeddingTargetData $target, ?string $batchToken): Builder
    {
        if ($target === null || $target->type === null) {
            $query->whereNull('target_type')->whereNull('target_id');
        } else {
            $query->forTarget($target->type, $target->id);
        }

        $target?->namespace === null
            ? $query->whereNull('namespace')
            : $query->inNamespace($target->namespace);

        $batchToken === null
            ? $query->whereNull('batch_token')
            : $query->where('batch_token', $batchToken);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function rowAttributes(
        EmbeddingVectorData $vector,
        Model|EmbeddingTargetData|null $target,
        ?EmbeddingTargetData $targetData,
        array $meta,
        ?string $batchToken,
        bool $isActive,
        mixed $now,
    ): array {
        return [
            'embeddable_type' => $target instanceof Model ? $target->getMorphClass() : null,
            'embeddable_id' => $target instanceof Model ? $target->getKey() : null,
            'target_type' => $targetData?->type,
            'target_id' => $targetData?->id === null ? null : (string) $targetData->id,
            'provider' => $vector->provider,
            'model' => $vector->model,
            'dimension' => $vector->dimension,
            'chunk_index' => $vector->chunk->index,
            'content' => $vector->chunk->content,
            'content_hash' => $vector->chunk->contentHash,
            'embedding' => $this->serializeEmbedding($vector->vector),
            'namespace' => $targetData?->namespace,
            'meta' => empty($meta) ? null : json_encode($meta, JSON_THROW_ON_ERROR),
            'batch_token' => $batchToken,
            'is_active' => $isActive,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function batchUpdateAttributes(array $row): array
    {
        unset($row['created_at']);

        return $row;
    }

    /**
     * @param  float[]  $embedding
     */
    private function serializeEmbedding(array $embedding): string
    {
        if ((new Embedding)->getConnection()->getDriverName() === 'pgsql') {
            return '['.implode(',', $embedding).']';
        }

        return json_encode($embedding, JSON_THROW_ON_ERROR);
    }

    private function identityKey(EmbeddingVectorData $vector, ?EmbeddingTargetData $target, ?string $batchToken): string
    {
        $targetType = $target instanceof EmbeddingTargetData ? $target->type : null;
        $targetId = $target instanceof EmbeddingTargetData ? $target->id : null;
        $namespace = $target instanceof EmbeddingTargetData ? $target->namespace : null;

        return implode('|', [
            $targetType ?? '',
            $targetId === null ? '' : (string) $targetId,
            $namespace ?? '',
            $vector->provider,
            $vector->model,
            (string) $vector->chunk->index,
            $vector->chunk->contentHash,
            $batchToken ?? '',
        ]);
    }

    private function recordIdentityKey(Embedding $record): string
    {
        return implode('|', [
            $record->target_type ?? '',
            $record->target_id ?? '',
            $record->namespace ?? '',
            $record->provider,
            $record->model,
            (string) $record->chunk_index,
            $record->content_hash,
            $record->batch_token ?? '',
        ]);
    }

    private function applyMetaFilters(Builder $query, mixed $metaFilters): Builder
    {
        if (! is_array($metaFilters)) {
            return $query;
        }

        foreach ($metaFilters as $key => $value) {
            if (is_string($key)) {
                $query->withMetaFilter($key, $value);
            }
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function maxDistanceFromFilters(array $filters): ?float
    {
        $minScore = $filters['min_score'] ?? null;

        if (! is_int($minScore) && ! is_float($minScore)) {
            return null;
        }

        if ($minScore < 0.0 || $minScore > 1.0 || ! is_finite((float) $minScore)) {
            throw new UnexpectedValueException('The [min_score] filter must be a finite number between 0 and 1.');
        }

        return round(1.0 - (float) $minScore, 6);
    }

    private function applyTargetFilters(
        Builder $query,
        ?EmbeddingTargetData $target,
    ): Builder {
        if ($target === null || $target->type === null) {
            return $query->whereNull('target_type')->whereNull('target_id');
        }

        $query->forTarget($target->type, $target->id);

        if ($target->namespace !== null) {
            $query->inNamespace($target->namespace);
        }

        return $query;
    }
}
