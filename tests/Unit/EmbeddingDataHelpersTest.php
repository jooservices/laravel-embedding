<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchStatusData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EmbeddingDataHelpersTest extends TestCase
{
    public function test_stored_embedding_score_returns_null_without_distance_and_scaled_score_with_distance(): void
    {
        $vector = EmbeddingVectorData::make(
            ChunkData::make('hello', 0, 0, 5),
            [0.1, 0.2],
            'ollama',
            'nomic-embed-text',
        );

        $withoutDistance = new StoredEmbeddingData(
            id: 1,
            vector: $vector,
            targetType: null,
            targetId: null,
            embeddableType: null,
            embeddableId: null,
            namespace: null,
            meta: [],
            distance: null,
            createdAt: now(),
            updatedAt: now(),
        );

        $withDistance = new StoredEmbeddingData(
            id: 2,
            vector: $vector,
            targetType: null,
            targetId: null,
            embeddableType: null,
            embeddableId: null,
            namespace: null,
            meta: [],
            distance: 0.125,
            createdAt: now(),
            updatedAt: now(),
        );

        $this->assertNull($withoutDistance->score());
        $this->assertSame(0.875, $withDistance->score());
    }

    public function test_embedding_batch_status_progress_percentage_handles_zero_and_partial_progress(): void
    {
        $pending = new EmbeddingBatchStatusData(
            id: 'batch-1',
            targetType: null,
            targetId: null,
            namespace: null,
            provider: 'ollama',
            model: 'nomic-embed-text',
            status: 'pending',
            totalChunks: 0,
            completedChunks: 0,
            failedChunks: 0,
            replaceExisting: false,
            skipIfUnchanged: false,
            stagedBatchToken: null,
            sourceFormat: 'text',
            sourcePath: null,
            summary: null,
            completedAt: null,
            createdAt: now(),
            updatedAt: now(),
        );

        $running = new EmbeddingBatchStatusData(
            id: 'batch-2',
            targetType: 'document',
            targetId: 'doc-1',
            namespace: 'kb',
            provider: 'ollama',
            model: 'nomic-embed-text',
            status: 'running',
            totalChunks: 4,
            completedChunks: 2,
            failedChunks: 1,
            replaceExisting: true,
            skipIfUnchanged: true,
            stagedBatchToken: 'batch-2',
            sourceFormat: 'markdown',
            sourcePath: '/tmp/file.md',
            summary: null,
            completedAt: null,
            createdAt: now(),
            updatedAt: now(),
        );

        $this->assertSame(0.0, $pending->progressPercentage());
        $this->assertSame(75.0, $running->progressPercentage());
    }
}
