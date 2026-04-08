<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Exceptions;

use RuntimeException;

final class InvalidEmbeddingDimensionException extends RuntimeException
{
    /**
     * Create an instance for a dimension mismatch.
     */
    public static function mismatch(int $expected, int $actual): self
    {
        return new self(
            "Embedding dimension mismatch: expected [{$expected}], received [{$actual}].",
        );
    }

    /**
     * Create an instance for an empty vector.
     */
    public static function emptyVector(): self
    {
        return new self('Embedding vector must not be empty.');
    }
}
