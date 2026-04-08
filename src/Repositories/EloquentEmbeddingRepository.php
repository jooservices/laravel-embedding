<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Repositories;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;
use JOOservices\LaravelEmbedding\Models\Embedding;

final class EloquentEmbeddingRepository implements EmbeddingRepository
{
    public function store(EmbeddingVectorData $vector, ?Model $target = null, array $meta = []): StoredEmbeddingData
    {
        $record = Embedding::create([
            'embeddable_type' => $target?->getMorphClass(),
            'embeddable_id' => $target?->getKey(),
            'provider' => $vector->provider,
            'model' => $vector->model,
            'dimension' => $vector->dimension,
            'chunk_index' => $vector->chunk->index,
            'content' => $vector->chunk->content,
            'content_hash' => $vector->chunk->contentHash,
            'embedding' => $vector->vector,
            'meta' => empty($meta) ? null : $meta,
        ]);

        return $this->toDto($record, $vector);
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @return StoredEmbeddingData[]
     */
    public function storeBatch(array $vectors, ?Model $target = null, array $meta = []): array
    {
        return array_map(
            fn (EmbeddingVectorData $vector): StoredEmbeddingData => $this->store($vector, $target, $meta),
            $vectors,
        );
    }

    public function deleteForTarget(Model $target): int
    {
        return Embedding::query()
            ->where('embeddable_type', $target->getMorphClass())
            ->where('embeddable_id', $target->getKey())
            ->delete();
    }

    public function findByHash(string $contentHash): ?StoredEmbeddingData
    {
        $record = Embedding::query()
            ->where('content_hash', $contentHash)
            ->first();

        if ($record === null) {
            return null;
        }

        // Reconstruct the ChunkData and EmbeddingVectorData from the record
        // so we can return a fully typed DTO without leaking the Eloquent model.
        $chunk = \JOOservices\LaravelEmbedding\DTOs\ChunkData::make(
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

    public function searchSimilar(array $embedding, int $limit = 5): \Illuminate\Support\Collection
    {
        return Embedding::query()
            ->nearestTo($embedding)
            ->limit($limit)
            ->get()
            ->map(function (Embedding $record): StoredEmbeddingData {
                $chunk = \JOOservices\LaravelEmbedding\DTOs\ChunkData::make(
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
            });
    }

    /**
     * Map an Eloquent Embedding record + vector DTO to a StoredEmbeddingData.
     */
    private function toDto(Embedding $record, EmbeddingVectorData $vector): StoredEmbeddingData
    {
        return new StoredEmbeddingData(
            id: $record->getKey(),
            vector: $vector,
            embeddableType: $record->embeddable_type,
            embeddableId: $record->embeddable_id,
            meta: $record->meta ?? [],
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
        );
    }
}
