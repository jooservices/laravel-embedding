<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;
use JOOservices\LaravelEmbedding\Facades\Embedding;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EmbeddingManagerTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_VECTOR = [0.1, 0.2, 0.3, 0.4];

    private const OLLAMA_URL = 'http://localhost:11434/api/embed';

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Disable persistence for manager-level tests — tested separately.
        $app['config']->set('embedding.database.enabled', false);
    }

    private function fakeOllamaSuccess(int $count = 1): void
    {
        $embeddings = array_fill(0, $count, self::FAKE_VECTOR);

        Http::fake([
            self::OLLAMA_URL => Http::response([
                'model' => 'nomic-embed-text',
                'embeddings' => $embeddings,
            ], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // Container binding
    // -------------------------------------------------------------------------

    public function test_service_provider_binds_embedding_manager_contract(): void
    {
        $manager = $this->app->make(EmbeddingManager::class);

        $this->assertInstanceOf(EmbeddingManager::class, $manager);
    }

    public function test_facade_resolves_embedding_manager(): void
    {
        $this->fakeOllamaSuccess();

        $result = Embedding::embedText('Hello Facade');

        $this->assertInstanceOf(EmbeddingVectorData::class, $result);
    }

    // -------------------------------------------------------------------------
    // embedText
    // -------------------------------------------------------------------------

    public function test_embed_text_returns_vector_data(): void
    {
        $this->fakeOllamaSuccess();

        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->embedText('The quick brown fox');

        $this->assertInstanceOf(EmbeddingVectorData::class, $result);
        $this->assertSame('ollama', $result->provider);
        $this->assertSame(count(self::FAKE_VECTOR), $result->dimension);
    }

    public function test_embed_text_throws_on_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = $this->app->make(EmbeddingManager::class);
        $manager->embedText('   ');
    }

    // -------------------------------------------------------------------------
    // embedBatch
    // -------------------------------------------------------------------------

    public function test_embed_batch_returns_batch_result(): void
    {
        $this->fakeOllamaSuccess(3);

        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->embedBatch(['One', 'Two', 'Three']);

        $this->assertInstanceOf(EmbeddingBatchResultData::class, $result);
        $this->assertSame(3, $result->count());
    }

    public function test_embed_batch_with_empty_array_returns_empty_result(): void
    {
        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->embedBatch([]);

        $this->assertSame(0, $result->count());
    }

    // -------------------------------------------------------------------------
    // chunkText
    // -------------------------------------------------------------------------

    public function test_chunk_text_returns_chunk_data_array(): void
    {
        $manager = $this->app->make(EmbeddingManager::class);
        $chunks = $manager->chunkText(str_repeat('a', 250));

        $this->assertNotEmpty($chunks);
        $this->assertIsArray($chunks);
    }

    // -------------------------------------------------------------------------
    // chunkAndEmbed
    // -------------------------------------------------------------------------

    public function test_chunk_and_embed_returns_batch_result(): void
    {
        // 250 chars / 100 chunk size = 3 chunks (with no overlap)
        $this->fakeOllamaSuccess(3);

        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->chunkAndEmbed(str_repeat('x', 250));

        $this->assertInstanceOf(EmbeddingBatchResultData::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }

    // -------------------------------------------------------------------------
    // HTTP error propagation
    // -------------------------------------------------------------------------

    public function test_embed_text_throws_embedding_failed_on_http_error(): void
    {
        $this->expectException(EmbeddingFailedException::class);

        Http::fake([
            self::OLLAMA_URL => Http::response('Internal Server Error', 500),
        ]);

        $manager = $this->app->make(EmbeddingManager::class);
        $manager->embedText('Trigger HTTP failure');
    }

    // -------------------------------------------------------------------------
    // EmbeddingBatchResultData helpers
    // -------------------------------------------------------------------------

    public function test_batch_result_to_vector_array(): void
    {
        $this->fakeOllamaSuccess(2);

        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->embedBatch(['Hello', 'World']);

        $this->assertInstanceOf(EmbeddingBatchResultData::class, $result);
        $vectors = $result->toVectorArray();
        $this->assertCount(2, $vectors);
        $this->assertIsArray($vectors[0]);
    }

    // -------------------------------------------------------------------------
    // EmbeddingManager with persistence enabled + target model
    // -------------------------------------------------------------------------

    public function test_embed_text_with_persistence_and_target(): void
    {
        $this->fakeOllamaSuccess();

        // Use the default config (persistence enabled)
        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->embedText('Persist me', [
            'chunk_size' => 200,
            'chunk_overlap' => 20,
        ]);

        $this->assertInstanceOf(EmbeddingVectorData::class, $result);
    }

    public function test_embed_batch_with_custom_chunk_size_in_context(): void
    {
        $this->fakeOllamaSuccess(2);

        $manager = $this->app->make(EmbeddingManager::class);
        $result = $manager->embedBatch(['Alpha', 'Beta']);

        $this->assertSame(2, $result->count());
        $this->assertSame('ollama', $result->provider);
    }

    public function test_chunk_text_with_custom_context_size(): void
    {
        $manager = $this->app->make(EmbeddingManager::class);
        $chunks = $manager->chunkText(str_repeat('b', 50), ['chunk_size' => 20, 'chunk_overlap' => 0]);

        $this->assertGreaterThan(1, count($chunks));
    }

    public function test_chunk_preview_returns_debug_summary(): void
    {
        $manager = $this->app->make(EmbeddingManager::class);
        $preview = $manager->chunkPreview(str_repeat('preview ', 20), ['chunk_size' => 20, 'chunk_overlap' => 0]);

        $this->assertNotEmpty($preview);
        $this->assertArrayHasKey('preview', $preview[0]);
        $this->assertArrayHasKey('content_hash', $preview[0]);
    }
}
