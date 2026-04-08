<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchStatusData;
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
     * Embed a single pre-built chunk and optionally persist the result.
     * Called by ProcessChunkJob as the leaf step of the fan-out pipeline.
     *
     * @param  array<string, mixed>  $context
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     */
    public function embedChunk(ChunkData $chunk, array $context = []): EmbeddingVectorData;

    /**
     * Chunk text and dispatch one ProcessChunkJob per chunk (fan-out).
     * Prefer this over queueBatch when individual chunk-level concurrency is needed.
     *
     * @param  array<string, mixed>  $context
     */
    public function queueChunked(string $text, array $context = []): void;

    /**
     * Dispatch a tracked fan-out batch and return the batch status DTO.
     *
     * @param  array<string, mixed>  $context
     */
    public function queueTracked(string $text, array $context = []): EmbeddingBatchStatusData;

    /**
     * Dispatch a background job to chunk and embed the text asynchronously.
     *
     * @param  array<string, mixed>  $context
     */
    public function queueBatch(string $text, array $context = []): \Illuminate\Foundation\Bus\PendingDispatch;

    /**
     * Read the persisted status of a tracked batch.
     */
    public function batchStatus(string $batchId): ?EmbeddingBatchStatusData;

    public function ingestHtml(string $html, array $context = []): EmbeddingBatchResultData;

    public function ingestMarkdown(string $markdown, array $context = []): EmbeddingBatchResultData;

    public function ingestFile(string $path, array $context = []): EmbeddingBatchResultData;

    public function queueHtml(string $html, array $context = []): EmbeddingBatchStatusData;

    public function queueMarkdown(string $markdown, array $context = []): EmbeddingBatchStatusData;

    public function queueFile(string $path, array $context = []): EmbeddingBatchStatusData;

    /**
     * Return a developer-friendly summary of how the current chunker would split the text.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function chunkPreview(string $text, array $context = []): array;
}
