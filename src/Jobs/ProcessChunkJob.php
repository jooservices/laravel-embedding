<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;
use Throwable;

/**
 * Background job to process a single chunk.
 * Dispatched automatically by EmbeddingManager::queueChunked or ProcessEmbeddingBatchJob.
 */
class ProcessChunkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly ChunkData $chunk,
        public readonly array $context = [],
        public readonly ?string $concurrencyKey = null,
        public readonly ?int $tries = null,
        public readonly int|array|null $backoff = null,
        public readonly ?int $timeout = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmbeddingManager $manager): void
    {
        $manager->embedChunk($this->chunk, $this->context);

        $batchId = $this->context['batch_id'] ?? null;
        if (! is_string($batchId) || $batchId === '') {
            return;
        }

        $status = app(EmbeddingBatchTracker::class)->markChunkSucceeded($batchId);
        if ($status === null || $status->status !== 'completed' || $status->stagedBatchToken === null) {
            return;
        }

        $target = EmbeddingTargetData::fromContext($this->context);
        if ($target === null && ! isset($this->context['target'])) {
            return;
        }

        if (! app()->bound(EmbeddingRepository::class)) {
            return;
        }

        app(EmbeddingRepository::class)->activateStagedBatch(
            $this->context['target'] ?? $target,
            $status->stagedBatchToken,
        );
    }

    public function failed(?Throwable $exception): void
    {
        $batchId = $this->context['batch_id'] ?? null;
        if (! is_string($batchId) || $batchId === '') {
            return;
        }

        $message = $exception?->getMessage() ?? 'Chunk job failed.';
        app(EmbeddingBatchTracker::class)->markChunkFailed($batchId, $message);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        if ($this->concurrencyKey === null || $this->concurrencyKey === '') {
            return [];
        }

        return [
            (new WithoutOverlapping($this->concurrencyKey))->shared(),
        ];
    }
}
