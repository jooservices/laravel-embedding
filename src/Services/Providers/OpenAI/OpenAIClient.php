<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\OpenAI;

use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;

/**
 * Placeholder OpenAI HTTP client.
 *
 * Full implementation is deferred to a future package version.
 * The structural contract is maintained to demonstrate extensibility.
 */
final class OpenAIClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 30,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws EmbeddingFailedException
     */
    public function embeddings(array $payload): array
    {
        throw EmbeddingFailedException::fromProviderError(
            provider: 'openai',
            reason: 'OpenAI provider is not yet implemented. Planned for V2.',
        );
    }
}
