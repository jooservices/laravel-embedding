<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;

interface EmbeddingManager
{
    /**
     * Embed a single piece of text.
     * The text is NOT chunked — it is embedded as-is.
     * Use chunkAndEmbed() for large texts that require chunking.
     *
     * @param  array<string, mixed>  $context  Optional metadata (e.g., source, tags).
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     */
    public function embedText(string $text, array $context = []): EmbeddingVectorData;

    /**
     * Embed a batch of texts as individual inputs.
     * Each item is embedded independently.
     *
     * @param  string[]  $texts
     * @param  array<string, mixed>  $context
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     */
    public function embedBatch(array $texts, array $context = []): EmbeddingBatchResultData;

    /**
     * Split text into chunks and return the chunk DTOs without embedding.
     *
     * @return ChunkData[]
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\ChunkingException
     */
    public function chunkText(string $text, array $context = []): array;

    /**
     * Chunk the text and embed each resulting chunk.
     * This is the primary entry point for large-text embedding workflows.
     *
     * @param  array<string, mixed>  $context
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\ChunkingException
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     */
    public function chunkAndEmbed(string $text, array $context = []): EmbeddingBatchResultData;

    /**
     * Dispatch a background job to chunk and embed the text asynchronously.
     *
     * @param  array<string, mixed>  $context
     */
    public function queueBatch(string $text, array $context = []): \Illuminate\Foundation\Bus\PendingDispatch;

    /**
     * Return a developer-friendly summary of how the current chunker would split the text.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function chunkPreview(string $text, array $context = []): array;
}
