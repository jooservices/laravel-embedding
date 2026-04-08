<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\OpenAI;

use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;

/**
 * Placeholder OpenAI adapter implementing EmbeddingProvider.
 *
 * Included in V1 to verify the provider contract is clean and extensible.
 * All methods throw until a full implementation is written in V2.
 */
final class OpenAIEmbeddingAdapter implements EmbeddingProvider
{
    public function __construct(
        private readonly OpenAIClient $client,
        private readonly OpenAIEmbeddingResponseNormalizer $normalizer,
        private readonly string $model,
    ) {}

    /** @throws EmbeddingFailedException */
    public function embed(ChunkData $chunk): EmbeddingVectorData
    {
        throw EmbeddingFailedException::fromProviderError(
            provider: 'openai',
            reason: 'OpenAI provider is not yet implemented. Planned for V2.',
        );
    }

    /** @throws EmbeddingFailedException */
    public function embedBatch(array $chunks): EmbeddingBatchResultData
    {
        throw EmbeddingFailedException::fromProviderError(
            provider: 'openai',
            reason: 'OpenAI provider is not yet implemented. Planned for V2.',
        );
    }

    public function providerName(): string
    {
        return 'openai';
    }

    public function modelName(): string
    {
        return $this->model;
    }
}
