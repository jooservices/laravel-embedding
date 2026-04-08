<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Embedding\Concerns;

use Illuminate\Support\Str;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchStatusData;
use JOOservices\LaravelEmbedding\Jobs\ProcessChunkJob;
use JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;

trait HandlesEmbeddingQueueBatches
{
    public function queueChunked(string $text, array $context = []): void
    {
        $chunks = $this->chunkText($text, $context);
        $target = $this->extractTarget($context);
        $batchId = $this->ensureQueueBatchId($context);

        if ($this->shouldSkipDispatch($chunks, $target, $context, $batchId)) {
            return;
        }

        $this->prepareStagedReplacement($context, $batchId, $target);
        $this->markBatchDispatched($batchId, $chunks);
        $this->dispatchChunkJobs($chunks, $context);
    }

    public function queueTracked(string $text, array $context = []): EmbeddingBatchStatusData
    {
        $tracked = $this->tracker()->ensureBatch(
            [...$context, 'provider' => $this->provider->providerName(), 'model' => $this->provider->modelName()],
            $context['source_format'] ?? 'text',
            $this->sourcePathFromContext($context),
        );

        $this->queueBatch($text, [...$context, 'batch_id' => $tracked->id]);

        return $tracked;
    }

    public function queueBatch(string $text, array $context = []): \Illuminate\Foundation\Bus\PendingDispatch
    {
        if (! isset($context['batch_id']) || ! is_string($context['batch_id']) || $context['batch_id'] === '') {
            $batch = $this->tracker()->ensureBatch(
                [...$context, 'provider' => $this->provider->providerName(), 'model' => $this->provider->modelName()],
                $context['source_format'] ?? 'text',
                $this->sourcePathFromContext($context),
            );
            $context['batch_id'] = $batch->id;
        }

        $job = new ProcessEmbeddingBatchJob(
            text: $text,
            context: $context,
            concurrencyKey: $this->resolveConcurrencyKey($context),
            tries: (int) ($context['queue_tries'] ?? $this->queueTries),
            backoff: $context['queue_backoff'] ?? $this->queueBackoff,
            timeout: (int) ($context['queue_timeout'] ?? $this->queueTimeout),
        );

        return dispatch($job)
            ->onConnection($context['queue_connection'] ?? $this->queueConnection)
            ->onQueue($context['queue_name'] ?? $this->queueName);
    }

    public function batchStatus(string $batchId): ?EmbeddingBatchStatusData
    {
        return $this->tracker()->find($batchId);
    }

    /**
     * @param  ChunkData[]  $chunks
     * @param  array<string, mixed>  $context
     */
    private function dispatchChunkJobs(array $chunks, array $context): void
    {
        foreach ($chunks as $chunk) {
            $job = new ProcessChunkJob(
                chunk: $chunk,
                context: $context,
                concurrencyKey: $this->resolveConcurrencyKey($context),
                tries: (int) ($context['queue_tries'] ?? $this->queueTries),
                backoff: $context['queue_backoff'] ?? $this->queueBackoff,
                timeout: (int) ($context['queue_timeout'] ?? $this->queueTimeout),
            );

            dispatch($job)
                ->onConnection($context['queue_connection'] ?? $this->queueConnection)
                ->onQueue($context['queue_name'] ?? $this->queueName);
        }
    }

    /**
     * @param  ChunkData[]  $chunks
     */
    private function markBatchDispatched(?string $batchId, array $chunks): void
    {
        if ($batchId === null) {
            return;
        }

        $this->tracker()->markDispatched(
            $batchId,
            count($chunks),
            $this->provider->providerName(),
            $this->provider->modelName(),
        );
    }

    private function prepareStagedReplacement(array &$context, ?string $batchId, mixed $target): void
    {
        if (! $this->persistenceEnabled || $this->repository === null || $target === null) {
            return;
        }

        if ($this->shouldReplaceExisting($context)) {
            $context['staged_batch_token'] ??= $batchId ?? (string) Str::uuid();
        }
    }

    /**
     * @param  ChunkData[]  $chunks
     */
    private function shouldSkipDispatch(array $chunks, mixed $target, array $context, ?string $batchId): bool
    {
        if (! $this->persistenceEnabled || $this->repository === null || $target === null || ! $this->shouldSkipUnchanged($context)) {
            return false;
        }

        $contentHashes = array_map(
            static fn (ChunkData $chunk): string => $chunk->contentHash,
            $chunks,
        );

        if (! $this->repository->hasMatchingContentHashes(
            $target,
            $contentHashes,
            $this->provider->providerName(),
            $this->provider->modelName(),
        )) {
            return false;
        }

        if ($batchId !== null) {
            $this->tracker()->markDispatched(
                $batchId,
                count($chunks),
                $this->provider->providerName(),
                $this->provider->modelName(),
                'Skipped unchanged target content.',
            );
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function ensureQueueBatchId(array &$context): ?string
    {
        $batchId = isset($context['batch_id']) && is_string($context['batch_id']) ? $context['batch_id'] : null;

        if (! $this->shouldReplaceExisting($context) || $batchId !== null) {
            return $batchId;
        }

        $tracked = $this->tracker()->ensureBatch(
            [...$context, 'provider' => $this->provider->providerName(), 'model' => $this->provider->modelName()],
            $context['source_format'] ?? 'text',
            $this->sourcePathFromContext($context),
        );

        $context['batch_id'] = $tracked->id;

        return $tracked->id;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function sourcePathFromContext(array $context): ?string
    {
        return isset($context['source_path']) && is_string($context['source_path']) ? $context['source_path'] : null;
    }

    private function tracker(): EmbeddingBatchTracker
    {
        return $this->batchTracker ?? new EmbeddingBatchTracker;
    }
}
