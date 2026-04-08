<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager;
use JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob;
use JOOservices\LaravelEmbedding\Models\EmbeddingBatch;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;
use RuntimeException;

final class ProcessEmbeddingBatchJobTest extends TestCase
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

    public function test_handle_calls_queue_chunked(): void
    {
        $manager = Mockery::mock(EmbeddingManager::class);
        $manager->shouldReceive('queueChunked')
            ->once()
            ->with('some text', ['foo' => 'bar']);

        $job = new ProcessEmbeddingBatchJob('some text', ['foo' => 'bar']);
        $job->handle($manager);

        $this->assertTrue(true);
    }

    public function test_middleware_returns_without_overlapping_when_concurrency_key_is_present(): void
    {
        $job = new ProcessEmbeddingBatchJob('some text', ['foo' => 'bar'], 'docs:1');

        $this->assertCount(1, $job->middleware());
    }

    public function test_middleware_returns_empty_when_concurrency_key_is_null(): void
    {
        $job = new ProcessEmbeddingBatchJob('some text', ['foo' => 'bar'], null);

        $this->assertCount(0, $job->middleware());
    }

    public function test_failed_marks_batch_as_failed(): void
    {
        EmbeddingBatch::query()->create([
            'id' => 'batch-dispatch',
            'status' => 'pending',
            'total_chunks' => 0,
            'completed_chunks' => 0,
            'failed_chunks' => 0,
        ]);

        app()->instance(EmbeddingBatchTracker::class, new EmbeddingBatchTracker);

        $job = new ProcessEmbeddingBatchJob('some text', ['batch_id' => 'batch-dispatch']);
        $job->failed(new RuntimeException('dispatch exploded'));

        $batch = EmbeddingBatch::query()->find('batch-dispatch');
        $this->assertNotNull($batch);
        $this->assertSame('failed', $batch->status);
        $this->assertSame(1, $batch->failed_chunks);
        $this->assertStringContainsString('dispatch exploded', (string) $batch->summary);
    }

    public function test_failed_returns_early_when_batch_id_is_missing(): void
    {
        $job = new ProcessEmbeddingBatchJob('some text');

        $job->failed(new RuntimeException('ignored'));

        $this->assertSame(0, EmbeddingBatch::query()->count());
    }
}
