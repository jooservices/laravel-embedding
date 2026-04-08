<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Exceptions\InvalidEmbeddingDimensionException;
use JOOservices\LaravelEmbedding\Models\Embedding;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingManager;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;
use RuntimeException;

final class EmbeddingModelAndManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function test_embedding_model_properties(): void
    {
        $this->assertSame('sqlite', (new Embedding)->getConnectionName());
        $this->assertSame('embeddings', (new Embedding)->getTable());

        Schema::create('embeddable_models', function (Blueprint $table) {
            $table->id();
            $table->string('content')->nullable();
            $table->timestamps();
        });

        $model = Embedding::create([
            'embedding' => '[1.0, 2.0]',
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimension' => 2,
            'chunk_index' => 0,
            'content' => 'hello',
            'content_hash' => 'hash',
        ]);

        $this->assertNull($model->embeddable);
    }

    public function test_scope_nearest_throws_on_sqlite(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Vector search is only supported on a PostgreSQL database');

        Embedding::query()->nearestTo([1.0, 2.0])->get();
    }

    public function test_invalid_embedding_dimension_exception(): void
    {
        $e = InvalidEmbeddingDimensionException::mismatch(10, 5);
        $this->assertStringContainsString('Embedding dimension mismatch: expected [10]', $e->getMessage());

        $e2 = InvalidEmbeddingDimensionException::emptyVector();
        $this->assertStringContainsString('Embedding vector must not be empty.', $e2->getMessage());
    }

    public function test_embedding_manager_dispatch_job(): void
    {
        Queue::fake();
        $manager = new EmbeddingManager(
            Mockery::mock(\JOOservices\LaravelEmbedding\Contracts\Chunker::class),
            Mockery::mock(\JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider::class),
            null,
            false,
            100,
            10,
        );
        $manager->queueBatch('some text', ['foo' => 'bar']);
        Queue::assertPushed(\JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob::class);
    }
}
