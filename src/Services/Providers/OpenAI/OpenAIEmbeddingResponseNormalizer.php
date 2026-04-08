<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\OpenAI;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;

/**
 * Placeholder OpenAI embedding response normalizer.
 * Deferred to V2.
 */
final class OpenAIEmbeddingResponseNormalizer
{
    /**
     * @throws EmbeddingFailedException
     */
    public function normalizeSingle(ChunkData $chunk, string $model, array $response): EmbeddingVectorData
    {
        throw EmbeddingFailedException::fromProviderError(
            provider: 'openai',
            reason: 'OpenAI provider is not yet implemented. Planned for V2.',
        );
    }

    /**
     * @param  ChunkData[]  $chunks
     * @return EmbeddingVectorData[]
     *
     * @throws EmbeddingFailedException
     */
    public function normalizeBatch(array $chunks, string $model, array $response): array
    {
        throw EmbeddingFailedException::fromProviderError(
            provider: 'openai',
            reason: 'OpenAI provider is not yet implemented. Planned for V2.',
        );
    }
}
