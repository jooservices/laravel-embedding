<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Embedding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchStatusData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\Models\EmbeddingBatch;

final class EmbeddingBatchTracker
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function ensureBatch(array $context, ?string $sourceFormat = null, ?string $sourcePath = null): EmbeddingBatchStatusData
    {
        $target = $this->extractTarget($context);
        $id = $this->resolveBatchId($context);

        $batch = EmbeddingBatch::query()->firstOrCreate(
            ['id' => $id],
            $this->defaultAttributes($context, $target, $sourceFormat, $sourcePath),
        );

        $this->syncOptionalAttributes($batch, $context, $sourceFormat, $sourcePath);

        if ($batch->isDirty()) {
            $batch->save();
        }

        return $this->toDto($batch);
    }

    public function markDispatched(string $batchId, int $totalChunks, ?string $provider, ?string $model, ?string $summary = null): ?EmbeddingBatchStatusData
    {
        $batch = EmbeddingBatch::query()->find($batchId);
        if ($batch === null) {
            return null;
        }

        $batch->fill([
            'provider' => $provider ?? $batch->provider,
            'model' => $model ?? $batch->model,
            'status' => $totalChunks === 0 ? 'completed' : 'running',
            'total_chunks' => $totalChunks,
            'summary' => $summary,
            'completed_at' => $totalChunks === 0 ? now() : null,
        ]);
        $batch->save();

        return $this->toDto($batch->fresh() ?? $batch);
    }

    public function markChunkSucceeded(string $batchId): ?EmbeddingBatchStatusData
    {
        $batch = EmbeddingBatch::query()->find($batchId);
        if ($batch === null) {
            return null;
        }

        $batch->increment('completed_chunks');
        $batch->refresh();

        if ($batch->completed_chunks + $batch->failed_chunks >= $batch->total_chunks) {
            $batch->status = $batch->failed_chunks > 0 ? 'failed' : 'completed';
            $batch->completed_at = now();
            $batch->save();
        }

        return $this->toDto($batch);
    }

    public function markChunkFailed(string $batchId, string $message): ?EmbeddingBatchStatusData
    {
        $batch = EmbeddingBatch::query()->find($batchId);
        if ($batch === null) {
            return null;
        }

        $batch->increment('failed_chunks');
        $batch->refresh();
        $batch->status = 'failed';
        $batch->summary = $this->appendSummary($batch->summary, $message);

        if ($batch->completed_chunks + $batch->failed_chunks >= $batch->total_chunks) {
            $batch->completed_at = now();
        }

        $batch->save();

        return $this->toDto($batch);
    }

    public function find(string $batchId): ?EmbeddingBatchStatusData
    {
        $batch = EmbeddingBatch::query()->find($batchId);

        return $batch === null ? null : $this->toDto($batch);
    }

    private function appendSummary(?string $current, string $message): string
    {
        $summary = trim(($current ?? '')."\n".$message);

        return Str::limit($summary, 1000, '...');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveBatchId(array $context): string
    {
        $batchId = $context['batch_id'] ?? null;

        return is_string($batchId) && $batchId !== '' ? $batchId : (string) Str::uuid();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function defaultAttributes(
        array $context,
        ?EmbeddingTargetData $target,
        ?string $sourceFormat,
        ?string $sourcePath,
    ): array {
        return [
            'target_type' => $target?->type,
            'target_id' => $target?->id === null ? null : (string) $target->id,
            'namespace' => $target?->namespace,
            'status' => 'pending',
            'total_chunks' => 0,
            'completed_chunks' => 0,
            'failed_chunks' => 0,
            'replace_existing' => (bool) ($context['replace_existing'] ?? false),
            'skip_if_unchanged' => (bool) ($context['skip_if_unchanged'] ?? false),
            'staged_batch_token' => $this->nullableString($context['staged_batch_token'] ?? null),
            'source_format' => $sourceFormat,
            'source_path' => $sourcePath,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function syncOptionalAttributes(
        EmbeddingBatch $batch,
        array $context,
        ?string $sourceFormat,
        ?string $sourcePath,
    ): void {
        $provider = $this->nullableString($context['provider'] ?? null);
        $model = $this->nullableString($context['model'] ?? null);

        if ($batch->provider === null && $provider !== null) {
            $batch->provider = $provider;
        }

        if ($batch->model === null && $model !== null) {
            $batch->model = $model;
        }

        if ($sourceFormat !== null) {
            $batch->source_format = $sourceFormat;
        }

        if ($sourcePath !== null) {
            $batch->source_path = $sourcePath;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function extractTarget(array $context): ?EmbeddingTargetData
    {
        $target = $context['target'] ?? null;
        if ($target instanceof EmbeddingTargetData) {
            return $target;
        }

        if ($target instanceof Model) {
            return EmbeddingTargetData::fromModel($target);
        }

        return EmbeddingTargetData::fromContext($context);
    }

    private function toDto(EmbeddingBatch $batch): EmbeddingBatchStatusData
    {
        return new EmbeddingBatchStatusData(
            id: $batch->id,
            targetType: $batch->target_type,
            targetId: $batch->target_id,
            namespace: $batch->namespace,
            provider: $batch->provider,
            model: $batch->model,
            status: $batch->status,
            totalChunks: $batch->total_chunks,
            completedChunks: $batch->completed_chunks,
            failedChunks: $batch->failed_chunks,
            replaceExisting: $batch->replace_existing,
            skipIfUnchanged: $batch->skip_if_unchanged,
            stagedBatchToken: $batch->staged_batch_token,
            sourceFormat: $batch->source_format,
            sourcePath: $batch->source_path,
            summary: $batch->summary,
            completedAt: $batch->completed_at,
            createdAt: $batch->created_at,
            updatedAt: $batch->updated_at,
        );
    }
}
