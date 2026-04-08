<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Models\Embedding;
use JOOservices\LaravelEmbedding\Repositories\EloquentEmbeddingRepository;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EloquentEmbeddingRepositoryStagingTest extends TestCase
{
    use RefreshDatabase;

    private EloquentEmbeddingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentEmbeddingRepository;
    }

    public function test_staged_embeddings_are_inactive_and_hidden_from_default_lookups(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-stage', 'kb');
        $vector = $this->makeVector(0);

        $stored = $this->repository->stage($vector, $target, ['source' => 'staged'], 'batch-1');
        $defaultResults = $this->repository->findForTarget($target);
        $withInactive = $this->repository->findForTarget($target, ['include_inactive' => true]);

        $this->assertSame('document', $stored->targetType);
        $this->assertNull($this->repository->findByHash($vector->chunk->contentHash));
        $this->assertCount(0, $defaultResults);
        $this->assertCount(1, $withInactive);
        $this->assertFalse((bool) Embedding::query()->first()?->is_active);
    }

    public function test_activate_staged_batch_promotes_new_rows_and_removes_previous_active_rows(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-activate', 'kb');

        $this->repository->storeBatch([$this->makeVector(0), $this->makeVector(1)], $target, ['version' => 'old']);
        $this->repository->stageBatch([$this->makeVector(2), $this->makeVector(3)], $target, ['version' => 'new'], 'batch-2');

        $activated = $this->repository->activateStagedBatch($target, 'batch-2');
        $active = $this->repository->findForTarget($target);
        $all = $this->repository->findForTarget($target, ['include_inactive' => true]);

        $this->assertSame(2, $activated);
        $this->assertSame([2, 3], $active->pluck('vector.chunk.index')->all());
        $this->assertCount(2, $all);
        $this->assertTrue(Embedding::query()->active()->count() === 2);
        $this->assertSame('new', $active->first()?->meta['version']);
    }

    public function test_delete_staged_batch_removes_only_inactive_rows_for_token(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-delete', 'kb');

        $this->repository->store($this->makeVector(0), $target);
        $this->repository->stage($this->makeVector(1), $target, [], 'batch-delete');
        $this->repository->stage($this->makeVector(2), $target, [], 'batch-keep');

        $deleted = $this->repository->deleteStagedBatch($target, 'batch-delete');
        $remaining = $this->repository->findForTarget($target, ['include_inactive' => true]);

        $this->assertSame(1, $deleted);
        $this->assertCount(2, $remaining);
        $this->assertSame([0, 2], $remaining->pluck('vector.chunk.index')->all());
    }

    public function test_has_matching_content_hashes_ignores_inactive_staged_rows(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-hashes', 'kb');
        $activeVectors = [$this->makeVector(0), $this->makeVector(1)];
        $stagedVector = $this->makeVector(2);

        $this->repository->storeBatch($activeVectors, $target);
        $this->repository->stage($stagedVector, $target, [], 'batch-hashes');

        $this->assertTrue($this->repository->hasMatchingContentHashes(
            $target,
            array_map(static fn (EmbeddingVectorData $vector): string => $vector->chunk->contentHash, $activeVectors),
            'ollama',
            'nomic-embed-text',
        ));

        $this->assertFalse($this->repository->hasMatchingContentHashes(
            $target,
            [$stagedVector->chunk->contentHash],
            'ollama',
            'nomic-embed-text',
        ));
    }

    private function makeVector(int $index): EmbeddingVectorData
    {
        return EmbeddingVectorData::make(
            ChunkData::make("Content {$index}", $index, $index * 10, ($index + 1) * 10),
            [0.1, 0.2, 0.3],
            'ollama',
            'nomic-embed-text',
        );
    }
}
