<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

/**
 * Structured input to an embedding provider.
 * Wraps a ChunkData with optional contextual metadata.
 */
final readonly class EmbedInputData
{
    public function __construct(
        /** The chunk to embed. */
        public ChunkData $chunk,

        /** Optional metadata forwarded alongside the embedding request. */
        public array $context = [],
    ) {}

    /**
     * Convenience factory from a raw string (wraps in a ChunkData at index 0).
     */
    public static function fromText(string $text, array $context = []): self
    {
        return new self(
            chunk: ChunkData::make(
                content: $text,
                index: 0,
                startOffset: 0,
                endOffset: mb_strlen($text),
            ),
            context: $context,
        );
    }
}
