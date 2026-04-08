<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Jobs\ProcessChunkJob;
use JOOservices\LaravelEmbedding\Models\EmbeddingBatch;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;
use RuntimeException;

final class ProcessChunkJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_calls_embed_chunk(): void
    {
        $manager = Mockery::mock(EmbeddingManager::class);
        $chunk = ChunkData::make('test chunk', 0, 0, 10);
        $context = ['foo' => 'bar'];

        $vector = EmbeddingVectorData::make($chunk, [0.1, 0.2], 'test', 'test');

        $manager->shouldReceive('embedChunk')
            ->once()
            ->with($chunk, $context)
            ->andReturn($vector);

        $job = new ProcessChunkJob($chunk, $context);
        $job->handle($manager);

        $this->assertTrue(true);
    }

    public function test_middleware_returns_without_overlapping_when_concurrency_key_is_present(): void
    {
        $chunk = ChunkData::make('test', 0, 0, 4);
        $job = new ProcessChunkJob($chunk, ['foo' => 'bar'], 'docs:1');

        $this->assertCount(1, $job->middleware());
    }

    public function test_middleware_returns_empty_when_concurrency_key_is_null(): void
    {
        $chunk = ChunkData::make('test', 0, 0, 4);
        $job = new ProcessChunkJob($chunk, ['foo' => 'bar'], null);

        $this->assertCount(0, $job->middleware());
    }

    public function test_handle_marks_batch_complete_and_activates_staged_batch(): void
    {
        $manager = Mockery::mock(EmbeddingManager::class);
        $repository = Mockery::mock(EmbeddingRepository::class);
        $tracker = new EmbeddingBatchTracker;

        $chunk = ChunkData::make('chunk text', 0, 0, 10);
        $target = new EmbeddingTargetData('document', 'doc-1', 'kb');

        EmbeddingBatch::query()->create([
            'id' => 'batch-1',
            'target_type' => 'document',
            'target_id' => 'doc-1',
            'namespace' => 'kb',
            'status' => 'running',
            'total_chunks' => 1,
            'completed_chunks' => 0,
            'failed_chunks' => 0,
            'staged_batch_token' => 'batch-1',
        ]);

        $manager->shouldReceive('embedChunk')
            ->once()
            ->with($chunk, [
                'batch_id' => 'batch-1',
                'target_type' => 'document',
                'target_id' => 'doc-1',
                'namespace' => 'kb',
            ])
            ->andReturn(EmbeddingVectorData::make($chunk, [0.1, 0.2], 'ollama', 'nomic-embed-text'));

        $repository->shouldReceive('activateStagedBatch')
            ->once()
            ->with(Mockery::type(EmbeddingTargetData::class), 'batch-1')
            ->andReturn(1);

        app()->instance(EmbeddingBatchTracker::class, $tracker);
        app()->instance(EmbeddingRepository::class, $repository);

        $job = new ProcessChunkJob($chunk, [
            'batch_id' => 'batch-1',
            'target_type' => 'document',
            'target_id' => 'doc-1',
            'namespace' => 'kb',
        ]);
        $job->handle($manager);

        $batch = EmbeddingBatch::query()->find('batch-1');
        $this->assertNotNull($batch);
        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->completed_chunks);
        $this->assertNotNull($batch->completed_at);
    }

    public function test_failed_marks_batch_as_failed(): void
    {
        $chunk = ChunkData::make('chunk text', 0, 0, 10);

        EmbeddingBatch::query()->create([
            'id' => 'batch-failed',
            'status' => 'running',
            'total_chunks' => 1,
            'completed_chunks' => 0,
            'failed_chunks' => 0,
        ]);

        app()->instance(EmbeddingBatchTracker::class, new EmbeddingBatchTracker);

        $job = new ProcessChunkJob($chunk, ['batch_id' => 'batch-failed']);
        $job->failed(new RuntimeException('chunk exploded'));

        $batch = EmbeddingBatch::query()->find('batch-failed');
        $this->assertNotNull($batch);
        $this->assertSame('failed', $batch->status);
        $this->assertSame(1, $batch->failed_chunks);
        $this->assertStringContainsString('chunk exploded', (string) $batch->summary);
    }

    public function test_handle_returns_without_activation_when_target_context_is_missing(): void
    {
        $manager = Mockery::mock(EmbeddingManager::class);
        $repository = Mockery::mock(EmbeddingRepository::class);

        $chunk = ChunkData::make('chunk text', 0, 0, 10);

        EmbeddingBatch::query()->create([
            'id' => 'batch-no-target',
            'status' => 'running',
            'total_chunks' => 1,
            'completed_chunks' => 0,
            'failed_chunks' => 0,
            'staged_batch_token' => 'batch-no-target',
        ]);

        $manager->shouldReceive('embedChunk')
            ->once()
            ->with($chunk, ['batch_id' => 'batch-no-target'])
            ->andReturn(EmbeddingVectorData::make($chunk, [0.1, 0.2], 'ollama', 'nomic-embed-text'));
        $repository->shouldNotReceive('activateStagedBatch');

        app()->instance(EmbeddingBatchTracker::class, new EmbeddingBatchTracker);
        app()->instance(EmbeddingRepository::class, $repository);

        (new ProcessChunkJob($chunk, ['batch_id' => 'batch-no-target']))->handle($manager);

        $this->assertSame('completed', EmbeddingBatch::query()->find('batch-no-target')?->status);
    }

    public function test_failed_returns_early_when_batch_id_is_missing(): void
    {
        $job = new ProcessChunkJob(ChunkData::make('chunk text', 0, 0, 10), []);

        $job->failed(new RuntimeException('ignored'));

        $this->assertSame(0, EmbeddingBatch::query()->count());
    }
}
