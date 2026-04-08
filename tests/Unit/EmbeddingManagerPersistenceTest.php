<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;
use JOOservices\LaravelEmbedding\Jobs\ProcessChunkJob;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingManager;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;

final class EmbeddingManagerPersistenceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_embed_text_stores_single_vector_when_replacement_is_not_requested(): void
    {
        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $vector = EmbeddingVectorData::make(
            chunk: ChunkData::make('hello', 0, 0, 5),
            vector: [0.1, 0.2],
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $provider->shouldReceive('embed')->once()->andReturn($vector);
        $repository->shouldReceive('store')
            ->once()
            ->with($vector, null, ['source' => 'unit'])
            ->andReturn($this->makeStoredEmbedding($vector));
        $repository->shouldNotReceive('replaceForTarget');

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10);
        $result = $manager->embedText('hello', ['source' => 'unit']);

        $this->assertSame($vector, $result);
    }

    public function test_chunk_and_embed_returns_existing_vectors_when_skip_if_unchanged_matches_target_hashes(): void
    {
        $target = new EmbeddingTargetData('docs', 'external-1', 'kb');

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $chunk = ChunkData::make('hello', 0, 0, 5);
        $vector = EmbeddingVectorData::make(
            chunk: $chunk,
            vector: [0.1, 0.2],
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $chunker->shouldReceive('chunk')->once()->andReturn([$chunk]);
        $provider->shouldReceive('providerName')->andReturn('ollama');
        $provider->shouldReceive('modelName')->andReturn('nomic-embed-text');
        $repository->shouldReceive('hasMatchingContentHashes')
            ->once()
            ->with($target, [$chunk->contentHash], 'ollama', 'nomic-embed-text')
            ->andReturn(true);
        $repository->shouldReceive('findForTarget')
            ->once()
            ->with($target, ['provider' => 'ollama', 'model' => 'nomic-embed-text'])
            ->andReturn(collect([$this->makeStoredEmbedding($vector, null, $target)]));
        $provider->shouldNotReceive('embedBatch');
        $repository->shouldNotReceive('storeBatch');
        $repository->shouldNotReceive('replaceForTarget');

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10);
        $result = $manager->chunkAndEmbed('hello', [
            'target' => $target,
            'skip_if_unchanged' => true,
        ]);

        $this->assertSame(1, $result->count());
        $this->assertSame($vector->vector, $result->vectors[0]->vector);
    }

    public function test_chunk_and_embed_replaces_target_embeddings_after_successful_generation(): void
    {
        $target = new class extends Model {};

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $chunk = ChunkData::make('hello', 0, 0, 5);
        $vector = EmbeddingVectorData::make(
            chunk: $chunk,
            vector: [0.1, 0.2],
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $chunker->shouldReceive('chunk')->once()->andReturn([$chunk]);
        $provider->shouldReceive('embedBatch')
            ->once()
            ->with([$chunk])
            ->andReturn(new EmbeddingBatchResultData([$vector], 'ollama', 'nomic-embed-text'));
        $repository->shouldReceive('replaceForTarget')
            ->once()
            ->with([$vector], $target, ['source' => 'unit'])
            ->andReturn([$this->makeStoredEmbedding($vector, $target)]);
        $repository->shouldNotReceive('storeBatch');

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10);
        $result = $manager->chunkAndEmbed('hello', [
            'target' => $target,
            'replace_existing' => true,
            'source' => 'unit',
        ]);

        $this->assertSame(1, $result->count());
    }

    public function test_queue_chunked_returns_early_when_target_hashes_are_unchanged(): void
    {
        Queue::fake();

        $target = new EmbeddingTargetData('docs', 'external-1', 'kb');

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $first = ChunkData::make('one', 0, 0, 3);
        $second = ChunkData::make('two', 1, 0, 3);

        $chunker->shouldReceive('chunk')->once()->andReturn([$first, $second]);
        $provider->shouldReceive('providerName')->once()->andReturn('ollama');
        $provider->shouldReceive('modelName')->once()->andReturn('nomic-embed-text');
        $repository->shouldReceive('hasMatchingContentHashes')
            ->once()
            ->with($target, [$first->contentHash, $second->contentHash], 'ollama', 'nomic-embed-text')
            ->andReturn(true);
        $repository->shouldNotReceive('deleteForTarget');

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10);
        $manager->queueChunked('hello', [
            'target' => $target,
            'skip_if_unchanged' => true,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_queue_chunked_stages_replacement_before_dispatching_chunk_jobs(): void
    {
        Queue::fake();
        Schema::create('embedding_batches', static function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->string('namespace')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('completed_chunks')->default(0);
            $table->unsignedInteger('failed_chunks')->default(0);
            $table->boolean('replace_existing')->default(false);
            $table->boolean('skip_if_unchanged')->default(false);
            $table->string('staged_batch_token')->nullable();
            $table->string('source_format', 32)->nullable();
            $table->string('source_path')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        $target = new EmbeddingTargetData('docs', 'external-1', 'kb');

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $first = ChunkData::make('one', 0, 0, 3);
        $second = ChunkData::make('two', 1, 0, 3);

        $chunker->shouldReceive('chunk')->once()->andReturn([$first, $second]);
        $provider->shouldReceive('providerName')->twice()->andReturn('ollama');
        $provider->shouldReceive('modelName')->twice()->andReturn('nomic-embed-text');
        $repository->shouldNotReceive('deleteForTarget');
        $repository->shouldNotReceive('hasMatchingContentHashes');

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10);
        $manager->queueChunked('hello', [
            'target' => $target,
            'replace_existing' => true,
        ]);

        Queue::assertPushed(ProcessChunkJob::class, static function (ProcessChunkJob $job): bool {
            return is_string($job->context['batch_id'] ?? null)
                && ($job->context['staged_batch_token'] ?? null) === ($job->context['batch_id'] ?? null)
                && $job->context['target'] instanceof EmbeddingTargetData;
        });
        Queue::assertPushed(ProcessChunkJob::class, 2);
    }

    public function test_queue_chunked_dispatches_without_tracking_when_persistence_is_disabled(): void
    {
        Queue::fake();

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);

        $chunk = ChunkData::make('hello', 0, 0, 5);

        $chunker->shouldReceive('chunk')->once()->andReturn([$chunk]);

        $manager = new EmbeddingManager($chunker, $provider, null, null, null, false, 100, 10);
        $manager->queueChunked('hello');

        Queue::assertPushed(ProcessChunkJob::class, 1);
    }

    public function test_embed_chunk_skips_persistence_when_matching_chunk_index_already_exists(): void
    {
        $target = new EmbeddingTargetData('docs', 'external-1', 'kb');

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $chunk = ChunkData::make('hello', 2, 0, 5);
        $vector = EmbeddingVectorData::make(
            chunk: $chunk,
            vector: [0.1, 0.2],
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $provider->shouldReceive('embed')->once()->with($chunk)->andReturn($vector);
        $provider->shouldReceive('providerName')->once()->andReturn('ollama');
        $provider->shouldReceive('modelName')->once()->andReturn('nomic-embed-text');
        $repository->shouldReceive('findForTarget')
            ->once()
            ->with($target, [
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'chunk_index' => 2,
            ])
            ->andReturn(collect([$this->makeStoredEmbedding($vector, null, $target)]));
        $repository->shouldNotReceive('store');

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10);
        $result = $manager->embedChunk($chunk, [
            'target' => $target,
            'skip_if_unchanged' => true,
        ]);

        $this->assertSame($vector->vector, $result->vector);
    }

    public function test_embed_batch_splits_provider_calls_when_batch_size_is_configured(): void
    {
        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $first = ChunkData::make('one', 0, 0, 3);
        $second = ChunkData::make('two', 1, 0, 3);
        $third = ChunkData::make('three', 2, 0, 5);

        $provider->shouldReceive('embedBatch')->once()->with([$first, $second])
            ->andReturn(new EmbeddingBatchResultData([
                EmbeddingVectorData::make($first, [0.1], 'ollama', 'nomic-embed-text'),
                EmbeddingVectorData::make($second, [0.2], 'ollama', 'nomic-embed-text'),
            ], 'ollama', 'nomic-embed-text'));
        $provider->shouldReceive('embedBatch')->once()->with([$third])
            ->andReturn(new EmbeddingBatchResultData([
                EmbeddingVectorData::make($third, [0.3], 'ollama', 'nomic-embed-text'),
            ], 'ollama', 'nomic-embed-text'));
        $provider->shouldReceive('providerName')->once()->andReturn('ollama');
        $provider->shouldReceive('modelName')->once()->andReturn('nomic-embed-text');
        $repository->shouldReceive('storeBatch')->once()->andReturn([]);

        $manager = new EmbeddingManager($chunker, $provider, $repository, null, null, true, 100, 10, 2);
        $result = $manager->embedBatch(['one', 'two', 'three']);

        $this->assertSame(3, $result->count());
    }

    private function makeStoredEmbedding(
        EmbeddingVectorData $vector,
        ?Model $target = null,
        ?EmbeddingTargetData $targetData = null,
    ): StoredEmbeddingData {
        return new StoredEmbeddingData(
            id: 1,
            vector: $vector,
            targetType: $targetData !== null ? $targetData->type : $target?->getMorphClass(),
            targetId: $targetData !== null ? $targetData->id : $target?->getKey(),
            embeddableType: $target?->getMorphClass(),
            embeddableId: $target?->getKey(),
            namespace: $targetData !== null ? $targetData->namespace : null,
            meta: [],
            distance: null,
            createdAt: now(),
            updatedAt: now(),
        );
    }
}
