<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;

interface EmbeddingProvider
{
    /**
     * Embed a single chunk of text and return a normalized vector.
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     * @throws \JOOservices\LaravelEmbedding\Exceptions\InvalidEmbeddingDimensionException
     */
    public function embed(ChunkData $chunk): EmbeddingVectorData;

    /**
     * Embed a batch of chunks in one provider call where supported.
     * Implementations that do not support native batch calls must
     * fall back to sequential single-embed calls internally.
     *
     * @param  ChunkData[]  $chunks
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     */
    public function embedBatch(array $chunks): EmbeddingBatchResultData;

    /**
     * Return the canonical provider identifier (e.g., "ollama", "openai").
     */
    public function providerName(): string;

    /**
     * Return the model name being used by this provider instance.
     */
    public function modelName(): string;
}
