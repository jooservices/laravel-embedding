<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

/**
 * Aggregates results from a batch embedding operation.
 */
final readonly class EmbeddingBatchResultData
{
    /**
     * @param  EmbeddingVectorData[]  $vectors  Ordered list of embedding results.
     */
    public function __construct(
        public array $vectors,

        /** The provider that processed this batch. */
        public string $provider,

        /** The model used for this batch. */
        public string $model,
    ) {}

    /**
     * Total number of vectors in this batch result.
     */
    public function count(): int
    {
        return count($this->vectors);
    }

    /**
     * Extract all raw float vectors as a plain 2D array.
     *
     * @return float[][]
     */
    public function toVectorArray(): array
    {
        return array_map(
            static fn (EmbeddingVectorData $v): array => $v->vector,
            $this->vectors,
        );
    }
}
