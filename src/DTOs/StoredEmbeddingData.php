<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

use DateTimeInterface;

/**
 * Represents an embedding record that has been persisted to storage.
 * This is a read-model DTO, not an Eloquent model.
 */
final readonly class StoredEmbeddingData
{
    public function __construct(
        /** Primary key of the persisted record. */
        public int|string $id,

        /** The full vector data that was stored. */
        public EmbeddingVectorData $vector,

        /** Package-level target type, if any. */
        public ?string $targetType,

        /** Package-level target ID, if any. */
        public int|string|null $targetId,

        /** Polymorphic target type, if any. */
        public ?string $embeddableType,

        /** Polymorphic target ID, if any. */
        public int|string|null $embeddableId,

        /** Optional package-level search namespace. */
        public ?string $namespace,

        /** Arbitrary metadata stored alongside the embedding. */
        public array $meta,

        public DateTimeInterface $createdAt,
        public DateTimeInterface $updatedAt,
    ) {}
}
