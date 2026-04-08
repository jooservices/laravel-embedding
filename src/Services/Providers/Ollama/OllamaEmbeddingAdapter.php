<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\Ollama;

use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;

/**
 * Ollama implementation of the EmbeddingProvider contract.
 *
 * Responsibilities:
 *  - Shape the embed request payload for Ollama's /api/embed endpoint.
 *  - Delegate HTTP transport to OllamaClient.
 *  - Delegate response mapping to OllamaEmbeddingResponseNormalizer.
 *
 * Ollama's /api/embed accepts an "input" field that can be either
 * a single string or an array of strings, enabling native batch support.
 *
 * Reference: https://github.com/ollama/ollama/blob/main/docs/api.md#generate-embeddings
 */
final class OllamaEmbeddingAdapter implements EmbeddingProvider
{
    public function __construct(
        private readonly OllamaClientInterface $client,
        private readonly OllamaEmbeddingResponseNormalizer $normalizer,
        private readonly string $model,
    ) {}

    public function embed(ChunkData $chunk): EmbeddingVectorData
    {
        $response = $this->client->embed([
            'model' => $this->model,
            'input' => $chunk->content,
        ]);

        return $this->normalizer->normalizeSingle($chunk, $this->model, $response);
    }

    /**
     * Embed a batch of chunks using Ollama's native multi-input support.
     * All inputs are sent in one HTTP request.
     *
     * @param  ChunkData[]  $chunks
     */
    public function embedBatch(array $chunks): EmbeddingBatchResultData
    {
        if (empty($chunks)) {
            return new EmbeddingBatchResultData(
                vectors: [],
                provider: $this->providerName(),
                model: $this->model,
            );
        }

        $response = $this->client->embed([
            'model' => $this->model,
            'input' => array_map(
                static fn (ChunkData $c): string => $c->content,
                $chunks,
            ),
        ]);

        $vectors = $this->normalizer->normalizeBatch($chunks, $this->model, $response);

        return new EmbeddingBatchResultData(
            vectors: $vectors,
            provider: $this->providerName(),
            model: $this->model,
        );
    }

    public function providerName(): string
    {
        return 'ollama';
    }

    public function modelName(): string
    {
        return $this->model;
    }
}
