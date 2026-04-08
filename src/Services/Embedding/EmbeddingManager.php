<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Embedding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager as EmbeddingManagerContract;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;

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
    public function __construct(
        private readonly Chunker $chunker,
        private readonly EmbeddingProvider $provider,
        private readonly ?EmbeddingRepository $repository,
        private readonly bool $persistenceEnabled,
        private readonly int $chunkSize,
        private readonly int $chunkOverlap,
    ) {}

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
            $this->repository->store($vector, $target, $meta);
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

        $result = $this->provider->embedBatch($chunks);

        if ($this->persistenceEnabled && $this->repository !== null) {
            $target = $this->extractTarget($context);
            $meta = $this->extractMeta($context);
            $this->repository->storeBatch($result->vectors, $target, $meta);
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
        $result = $this->provider->embedBatch($chunks);

        if ($this->persistenceEnabled && $this->repository !== null) {
            $target = $this->extractTarget($context);
            $meta = $this->extractMeta($context);
            $this->repository->storeBatch($result->vectors, $target, $meta);
        }

        return $result;
    }

    /**
     * Guard against empty text input.
     *
     * @throws InvalidArgumentException
     */
    private function assertNotEmpty(string $text, string $message = ''): void
    {
        if (trim($text) === '') {
            throw new InvalidArgumentException(
                $message !== '' ? $message : 'Input text must not be empty.',
            );
        }
    }

    /**
     * Extract the optional Eloquent model target from the context array.
     * Convention: pass the model under the key "target".
     */
    private function extractTarget(array $context): ?Model
    {
        $target = $context['target'] ?? null;

        return $target instanceof Model ? $target : null;
    }

    /**
     * Strip reserved context keys and return the remainder as arbitrary metadata.
     */
    private function extractMeta(array $context): array
    {
        $reserved = ['target', 'chunk_size', 'chunk_overlap'];

        return array_diff_key($context, array_flip($reserved));
    }

    public function queueBatch(string $text, array $context = []): \Illuminate\Foundation\Bus\PendingDispatch
    {
        return \JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob::dispatch($text, $context);
    }
}
