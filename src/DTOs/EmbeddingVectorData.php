<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

/**
 * Represents a normalized embedding vector result for a single chunk.
 */
final readonly class EmbeddingVectorData
{
    public function __construct(
        /** The source chunk this vector was generated from. */
        public ChunkData $chunk,

        /** The raw embedding vector (array of floats). */
        public array $vector,

        /** The embedding provider that produced this vector. */
        public string $provider,

        /** The model name used for embedding. */
        public string $model,

        /** Vector dimensionality — derived from count($vector). */
        public int $dimension,
    ) {}

    /**
     * Factory method that computes dimension automatically from the vector.
     */
    public static function make(
        ChunkData $chunk,
        array $vector,
        string $provider,
        string $model,
    ): self {
        return new self(
            chunk: $chunk,
            vector: $vector,
            provider: $provider,
            model: $model,
            dimension: count($vector),
        );
    }
}
