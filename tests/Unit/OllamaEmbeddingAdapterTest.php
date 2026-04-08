<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;
use JOOservices\LaravelEmbedding\Exceptions\InvalidEmbeddingDimensionException;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaClientInterface;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingAdapter;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingResponseNormalizer;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

final class OllamaEmbeddingAdapterTest extends TestCase
{
    private const MODEL = 'nomic-embed-text';

    private MockInterface $clientMock;

    private OllamaEmbeddingAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = Mockery::mock(OllamaClientInterface::class);

        $this->adapter = new OllamaEmbeddingAdapter(
            client: $this->clientMock,
            normalizer: new OllamaEmbeddingResponseNormalizer,
            model: self::MODEL,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Single embed
    // -------------------------------------------------------------------------

    public function test_embed_returns_vector_data_on_success(): void
    {
        $chunk = ChunkData::make('Hello embedding', 0, 0, 15);

        $this->clientMock
            ->shouldReceive('embed')
            ->once()
            ->with(['model' => self::MODEL, 'input' => 'Hello embedding'])
            ->andReturn([
                'model' => self::MODEL,
                'embeddings' => [[0.1, 0.2, 0.3, 0.4]],
            ]);

        $result = $this->adapter->embed($chunk);

        $this->assertSame('ollama', $result->provider);
        $this->assertSame(self::MODEL, $result->model);
        $this->assertCount(4, $result->vector);
        $this->assertSame(4, $result->dimension);
    }

    public function test_embed_throws_when_embeddings_key_missing(): void
    {
        $this->expectException(EmbeddingFailedException::class);

        $chunk = ChunkData::make('text', 0, 0, 4);

        $this->clientMock
            ->shouldReceive('embed')
            ->once()
            ->andReturn(['model' => self::MODEL]); // missing 'embeddings'

        $this->adapter->embed($chunk);
    }

    public function test_embed_throws_on_empty_vector(): void
    {
        $this->expectException(InvalidEmbeddingDimensionException::class);

        $chunk = ChunkData::make('text', 0, 0, 4);

        $this->clientMock
            ->shouldReceive('embed')
            ->once()
            ->andReturn([
                'model' => self::MODEL,
                'embeddings' => [[]], // empty vector
            ]);

        $this->adapter->embed($chunk);
    }

    // -------------------------------------------------------------------------
    // Batch embed
    // -------------------------------------------------------------------------

    public function test_embed_batch_sends_all_inputs_in_one_request(): void
    {
        $chunks = [
            ChunkData::make('First chunk', 0, 0, 11),
            ChunkData::make('Second chunk', 1, 11, 23),
        ];

        $this->clientMock
            ->shouldReceive('embed')
            ->once()
            ->with([
                'model' => self::MODEL,
                'input' => ['First chunk', 'Second chunk'],
            ])
            ->andReturn([
                'model' => self::MODEL,
                'embeddings' => [
                    [0.1, 0.2],
                    [0.3, 0.4],
                ],
            ]);

        $result = $this->adapter->embedBatch($chunks);

        $this->assertSame(2, $result->count());
        $this->assertSame('ollama', $result->provider);
    }

    public function test_embed_batch_with_empty_chunks_returns_empty_result(): void
    {
        $this->clientMock->shouldNotReceive('embed');

        $result = $this->adapter->embedBatch([]);

        $this->assertSame(0, $result->count());
    }

    public function test_embed_batch_throws_when_response_count_mismatches(): void
    {
        $this->expectException(EmbeddingFailedException::class);

        $chunks = [
            ChunkData::make('One', 0, 0, 3),
            ChunkData::make('Two', 1, 3, 6),
        ];

        $this->clientMock
            ->shouldReceive('embed')
            ->once()
            ->andReturn([
                'model' => self::MODEL,
                'embeddings' => [[0.1, 0.2]], // only 1 vector for 2 chunks
            ]);

        $this->adapter->embedBatch($chunks);
    }

    // -------------------------------------------------------------------------
    // Provider metadata
    // -------------------------------------------------------------------------

    public function test_provider_name_is_ollama(): void
    {
        $this->assertSame('ollama', $this->adapter->providerName());
    }

    public function test_model_name_is_configured_model(): void
    {
        $this->assertSame(self::MODEL, $this->adapter->modelName());
    }
}
