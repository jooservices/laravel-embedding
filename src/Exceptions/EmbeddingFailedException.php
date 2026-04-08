<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Exceptions;

use RuntimeException;

final class EmbeddingFailedException extends RuntimeException
{
    /**
     * Create an instance from a provider error response.
     */
    public static function fromProviderError(string $provider, string $reason, int $code = 0): self
    {
        return new self(
            message: "Embedding request to provider [{$provider}] failed: {$reason}",
            code: $code,
        );
    }
}
