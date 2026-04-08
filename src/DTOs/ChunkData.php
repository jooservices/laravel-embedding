<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

/**
 * Represents a single text chunk produced by the chunking service.
 */
final readonly class ChunkData
{
    public function __construct(
        /** The text content of this chunk. */
        public string $content,

        /** Zero-based position of this chunk within the original text. */
        public int $index,

        /** SHA-256 hash of the content, used for deduplication. */
        public string $contentHash,

        /** Character offset where this chunk begins in the original text. */
        public int $startOffset,

        /** Character offset where this chunk ends in the original text. */
        public int $endOffset,
    ) {}

    /**
     * Create a ChunkData from raw content and positional metadata.
     * The content hash is computed automatically.
     */
    public static function make(string $content, int $index, int $startOffset, int $endOffset): self
    {
        return new self(
            content: $content,
            index: $index,
            contentHash: hash('sha256', $content),
            startOffset: $startOffset,
            endOffset: $endOffset,
        );
    }
}
