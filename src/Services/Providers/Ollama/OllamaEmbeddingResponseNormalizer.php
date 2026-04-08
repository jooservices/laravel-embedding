<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\Ollama;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;
use JOOservices\LaravelEmbedding\Exceptions\InvalidEmbeddingDimensionException;

/**
 * Normalizes a raw Ollama API response into internal EmbeddingVectorData DTOs.
 *
 * The Ollama /api/embed endpoint returns shape:
 * {
 *     "model": "nomic-embed-text",
 *     "embeddings": [[0.1, 0.2, ...], [0.3, ...]]  // one per input
 * }
 *
 * Reference: https://github.com/ollama/ollama/blob/main/docs/api.md#generate-embeddings
 */
final class OllamaEmbeddingResponseNormalizer
{
    /**
     * Normalize a single-input embed response.
     *
     * @param  array<string, mixed>  $response  Raw decoded response from Ollama.
     *
     * @throws EmbeddingFailedException
     * @throws InvalidEmbeddingDimensionException
     */
    public function normalizeSingle(ChunkData $chunk, string $model, array $response): EmbeddingVectorData
    {
        $embeddings = $this->extractEmbeddings($response);

        if (count($embeddings) === 0) {
            throw EmbeddingFailedException::fromProviderError(
                provider: 'ollama',
                reason: 'Response contained no embedding vectors.',
            );
        }

        $vector = $embeddings[0];
        $this->assertValidVector($vector);

        return EmbeddingVectorData::make(
            chunk: $chunk,
            vector: $vector,
            provider: 'ollama',
            model: $model,
        );
    }

    /**
     * Normalize a batch embed response.
     * Ollama returns one embedding per input string in the same request.
     *
     * @param  ChunkData[]  $chunks
     * @param  array<string, mixed>  $response
     * @return EmbeddingVectorData[]
     *
     * @throws EmbeddingFailedException
     * @throws InvalidEmbeddingDimensionException
     */
    public function normalizeBatch(array $chunks, string $model, array $response): array
    {
        $embeddings = $this->extractEmbeddings($response);

        if (count($embeddings) !== count($chunks)) {
            throw EmbeddingFailedException::fromProviderError(
                provider: 'ollama',
                reason: sprintf(
                    'Expected %d embeddings in batch response, received %d.',
                    count($chunks),
                    count($embeddings),
                ),
            );
        }

        $results = [];
        foreach ($chunks as $i => $chunk) {
            $vector = $embeddings[$i];
            $this->assertValidVector($vector);

            $results[] = EmbeddingVectorData::make(
                chunk: $chunk,
                vector: $vector,
                provider: 'ollama',
                model: $model,
            );
        }

        return $results;
    }

    /**
     * Extract the 'embeddings' array from a raw API response.
     *
     * @return float[][]
     *
     * @throws EmbeddingFailedException
     */
    private function extractEmbeddings(array $response): array
    {
        if (! isset($response['embeddings']) || ! is_array($response['embeddings'])) {
            throw EmbeddingFailedException::fromProviderError(
                provider: 'ollama',
                reason: 'Response is missing the "embeddings" key or it is not an array.',
            );
        }

        return $response['embeddings'];
    }

    /**
     * Assert that an individual vector is a non-empty array of floats.
     *
     *
     * @throws InvalidEmbeddingDimensionException
     */
    private function assertValidVector(mixed $vector): void
    {
        if (! is_array($vector) || count($vector) === 0) {
            throw InvalidEmbeddingDimensionException::emptyVector();
        }
    }
}
