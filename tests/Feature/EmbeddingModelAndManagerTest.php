<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Exceptions\InvalidEmbeddingDimensionException;
use JOOservices\LaravelEmbedding\Facades\EmbeddingSearch;
use JOOservices\LaravelEmbedding\Models\Embedding;
use JOOservices\LaravelEmbedding\Models\EmbeddingBatch;
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

    public function test_embedding_search_facade_resolves_bound_service(): void
    {
        $this->assertInstanceOf(
            \JOOservices\LaravelEmbedding\Services\Search\EmbeddingSearchService::class,
            EmbeddingSearch::getFacadeRoot(),
        );
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
        $provider = Mockery::mock(\JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider::class);
        $provider->shouldReceive('providerName')->once()->andReturn('ollama');
        $provider->shouldReceive('modelName')->once()->andReturn('nomic-embed-text');

        $manager = new EmbeddingManager(
            Mockery::mock(\JOOservices\LaravelEmbedding\Contracts\Chunker::class),
            $provider,
            null,
            null,
            null,
            false,
            100,
            10,
        );
        $manager->queueBatch('some text', ['foo' => 'bar']);
        Queue::assertPushed(\JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob::class);
    }

    public function test_embedding_model_uses_overridden_connection_and_table_config(): void
    {
        config()->set('database.connections.custom-sqlite', config('database.connections.sqlite'));
        config()->set('embedding.database.connection', 'custom-sqlite');
        config()->set('embedding.database.table', 'custom_embeddings');
        config()->set('embedding.database.batch_table', 'custom_embedding_batches');

        $model = new Embedding;
        $batchModel = new EmbeddingBatch;

        $this->assertSame('custom-sqlite', $model->getConnectionName());
        $this->assertSame('custom_embeddings', $model->getTable());
        $this->assertSame('custom-sqlite', $batchModel->getConnectionName());
        $this->assertSame('custom_embedding_batches', $batchModel->getTable());
    }

    public function test_scope_for_target_without_target_id_only_filters_by_type(): void
    {
        $sql = Embedding::query()
            ->forTarget('document')
            ->toSql();

        $this->assertStringContainsString('"target_type" = ?', $sql);
        $this->assertStringNotContainsString('"target_id" = ?', $sql);
    }

    public function test_with_meta_filter_supports_null_for_sqlite(): void
    {
        $sql = Embedding::query()
            ->withMetaFilter('lang', null)
            ->toSql();

        $this->assertStringContainsString('json_extract("meta", \'$."lang"\') is null', $sql);
    }

    public function test_active_scope_filters_only_active_embeddings(): void
    {
        Embedding::query()->create([
            'embedding' => '[1.0, 2.0]',
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimension' => 2,
            'chunk_index' => 0,
            'content' => 'active',
            'content_hash' => 'hash-active',
            'is_active' => true,
        ]);
        Embedding::query()->create([
            'embedding' => '[3.0, 4.0]',
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimension' => 2,
            'chunk_index' => 1,
            'content' => 'inactive',
            'content_hash' => 'hash-inactive',
            'is_active' => false,
        ]);

        $active = Embedding::query()->active()->pluck('content')->all();

        $this->assertSame(['active'], $active);
    }

    public function test_provider_model_and_namespace_scopes_constrain_query(): void
    {
        $sql = Embedding::query()
            ->forProvider('ollama')
            ->forModel('nomic-embed-text')
            ->inNamespace('kb')
            ->toSql();

        $this->assertStringContainsString('"provider" = ?', $sql);
        $this->assertStringContainsString('"model" = ?', $sql);
        $this->assertStringContainsString('"namespace" = ?', $sql);
    }
}
