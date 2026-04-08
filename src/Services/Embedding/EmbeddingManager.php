<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Embedding;

use Illuminate\Support\Str;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager as EmbeddingManagerContract;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;
use JOOservices\LaravelEmbedding\Services\Embedding\Concerns\HandlesEmbeddingIngestion;
use JOOservices\LaravelEmbedding\Services\Embedding\Concerns\HandlesEmbeddingQueueBatches;
use JOOservices\LaravelEmbedding\Services\Embedding\Concerns\ManagesEmbeddingContext;
use JOOservices\LaravelEmbedding\Services\Ingestion\ContentNormalizer;

/**
 * Primary orchestration service for the embedding package.
 *
 * This class depends entirely on contracts — it has no knowledge of Ollama,
 * OpenAI, or any specific persistence driver. Those are injected via the
 * service container, allowing zero-change provider swaps at the config level.
 *
 * Flow:
 *   Application → EmbeddingManager (this class)
 *     → Chunker (splits text)
 *     → EmbeddingProvider adapter (calls provider)
 *     → EmbeddingRepository (persists, if enabled)
 *     → DTO result returned to application
 */
final class EmbeddingManager implements EmbeddingManagerContract
{
    use HandlesEmbeddingIngestion;
    use HandlesEmbeddingQueueBatches;
    use ManagesEmbeddingContext;

    public function __construct(
        private readonly Chunker $chunker,
        private readonly EmbeddingProvider $provider,
        private readonly ?EmbeddingRepository $repository,
        private readonly ?ContentNormalizer $normalizer,
        private readonly ?EmbeddingBatchTracker $batchTracker,
        private readonly bool $persistenceEnabled,
        private readonly int $chunkSize,
        private readonly int $chunkOverlap,
        private readonly int $providerBatchSize = 0,
        private readonly ?string $queueConnection = null,
        private readonly ?string $queueName = null,
        private readonly int $queueTries = 1,
        private readonly int|array $queueBackoff = 0,
        private readonly int $queueTimeout = 120,
    ) {
        // Dependencies are injected directly and need no additional boot logic.
    }

    /**
     * Embed a single piece of text without chunking.
     *
     * @param  array<string, mixed>  $context
     */
    public function embedText(string $text, array $context = []): EmbeddingVectorData
    {
        $this->assertNotEmpty($text);

        $chunk = ChunkData::make(
            content: $text,
            index: 0,
            startOffset: 0,
            endOffset: Str::length($text),
        );

        $vector = $this->provider->embed($chunk);

        if ($this->persistenceEnabled && $this->repository !== null) {
            $target = $this->extractTarget($context);
            $meta = $this->extractMeta($context);
            if ($this->shouldSkipUnchanged($context) && $target !== null) {
                $existing = $this->repository->findForTarget($target, [
                    'provider' => $this->provider->providerName(),
                    'model' => $this->provider->modelName(),
                ])->first();
                if ($existing !== null && $existing->vector->chunk->contentHash === $vector->chunk->contentHash) {
                    return $existing->vector;
                }
            }
            $this->persistVectors([$vector], $target, $meta, $this->shouldReplaceExisting($context));
        }

        return $vector;
    }

    /**
     * Embed a batch of independent texts — each is treated as one chunk.
     *
     * @param  string[]  $texts
     * @param  array<string, mixed>  $context
     */
    public function embedBatch(array $texts, array $context = []): EmbeddingBatchResultData
    {
        if (empty($texts)) {
            return new EmbeddingBatchResultData(
                vectors: [],
                provider: $this->provider->providerName(),
                model: $this->provider->modelName(),
            );
        }

        $chunks = [];
        foreach ($texts as $i => $text) {
            $this->assertNotEmpty($text, "Item at index [{$i}] is empty.");
            $chunks[] = ChunkData::make(
                content: $text,
                index: $i,
                startOffset: 0,
                endOffset: Str::length($text),
            );
        }

        $result = $this->embedChunks($chunks, $context);

        if ($this->persistenceEnabled && $this->repository !== null) {
            $target = $this->extractTarget($context);
            $meta = $this->extractMeta($context);
            $this->persistVectors($result->vectors, $target, $meta, $this->shouldReplaceExisting($context));
        }

        return $result;
    }

    /**
     * Chunk the text and return the chunk DTOs without embedding.
     *
     * @return ChunkData[]
     *
     * @throws ChunkingException
     */
    public function chunkText(string $text, array $context = []): array
    {
        $this->assertNotEmpty($text);

        $size = (int) ($context['chunk_size'] ?? $this->chunkSize);
        $overlap = (int) ($context['chunk_overlap'] ?? $this->chunkOverlap);

        return $this->chunker->chunk($text, $size, $overlap);
    }

    /**
     * Chunk and embed the full text in one call.
     * This is the primary entry point for large-text workflows.
     *
     * @param  array<string, mixed>  $context
     *
     * @throws ChunkingException
     * @throws EmbeddingFailedException
     */
    public function chunkAndEmbed(string $text, array $context = []): EmbeddingBatchResultData
    {
        $chunks = $this->chunkText($text, $context);
        $target = $this->extractTarget($context);

        if (
            $this->persistenceEnabled
            && $this->repository !== null
            && $target !== null
            && $this->shouldSkipUnchanged($context)
            && $this->repository->hasMatchingContentHashes(
                $target,
                array_map(static fn (ChunkData $chunk): string => $chunk->contentHash, $chunks),
                $this->provider->providerName(),
                $this->provider->modelName(),
            )
        ) {
            $existing = $this->repository->findForTarget($target, [
                'provider' => $this->provider->providerName(),
                'model' => $this->provider->modelName(),
            ]);

            return new EmbeddingBatchResultData(
                vectors: $existing->map(static fn ($stored): EmbeddingVectorData => $stored->vector)->all(),
                provider: $this->provider->providerName(),
                model: $this->provider->modelName(),
            );
        }

        $result = $this->embedChunks($chunks, $context);

        if ($this->persistenceEnabled && $this->repository !== null) {
            $meta = $this->extractMeta($context);
            $this->persistVectors($result->vectors, $target, $meta, $this->shouldReplaceExisting($context));
        }

        return $result;
    }

    public function embedChunk(ChunkData $chunk, array $context = []): EmbeddingVectorData
    {
        $vector = $this->provider->embed($chunk);

        if ($this->persistenceEnabled && $this->repository !== null) {
            $target = $this->extractTarget($context);
            $meta = $this->extractMeta($context);

            if ($this->shouldSkipUnchanged($context) && $target !== null) {
                $existing = $this->repository->findForTarget($target, [
                    'provider' => $this->provider->providerName(),
                    'model' => $this->provider->modelName(),
                    'chunk_index' => $chunk->index,
                ])->first();

                if ($existing !== null && $existing->vector->chunk->contentHash === $vector->chunk->contentHash) {
                    return $existing->vector;
                }
            }

            $stagedBatchToken = $context['staged_batch_token'] ?? null;

            if (is_string($stagedBatchToken) && $stagedBatchToken !== '' && $target !== null) {
                $this->repository->stage($vector, $target, $meta, $stagedBatchToken);
            } else {
                $this->persistVectors([$vector], $target, $meta, false);
            }
        }

        return $vector;
    }

    public function chunkPreview(string $text, array $context = []): array
    {
        return array_map(
            static fn (ChunkData $chunk): array => [
                'index' => $chunk->index,
                'content_hash' => $chunk->contentHash,
                'start_offset' => $chunk->startOffset,
                'end_offset' => $chunk->endOffset,
                'length' => Str::length($chunk->content),
                'preview' => Str::limit($chunk->content, 120),
            ],
            $this->chunkText($text, $context),
        );
    }

    /**
     * @param  ChunkData[]  $chunks
     * @param  array<string, mixed>  $context
     */
    private function embedChunks(array $chunks, array $context): EmbeddingBatchResultData
    {
        $batchSize = (int) ($context['batch_size'] ?? $this->providerBatchSize);
        if ($batchSize <= 0 || count($chunks) <= $batchSize) {
            return $this->provider->embedBatch($chunks);
        }

        $vectors = [];
        foreach (array_chunk($chunks, $batchSize) as $chunkBatch) {
            $result = $this->provider->embedBatch($chunkBatch);
            $vectors = [...$vectors, ...$result->vectors];
        }

        return new EmbeddingBatchResultData(
            vectors: $vectors,
            provider: $this->provider->providerName(),
            model: $this->provider->modelName(),
        );
    }
}
