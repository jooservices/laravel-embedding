<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Enums;

enum ChunkingStrategy: string
{
    /**
     * Default fixed-size chunking with configurable overlap.
     * Splits on character count boundaries.
     */
    case Default = 'default';

    /**
     * Sentence-aware chunking (future implementation).
     * Groups sentences until the size limit is reached.
     */
    case Sentence = 'sentence';

    /**
     * Paragraph-aware chunking (future implementation).
     * Groups paragraphs until the size limit is reached.
     */
    case Paragraph = 'paragraph';

    /**
     * Resolve from config value safely.
     */
    public static function fromConfig(string $value): self
    {
        return self::tryFrom($value) ?? self::Default;
    }
}
