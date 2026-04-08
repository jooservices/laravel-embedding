<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIClient;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIEmbeddingAdapter;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIEmbeddingResponseNormalizer;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class OpenAITest extends TestCase
{
    public function test_openai_client_throws(): void
    {
        $this->expectException(EmbeddingFailedException::class);
        $client = new OpenAIClient('url', 'key');
        $client->embeddings([]);
    }

    public function test_openai_normalizer_single_throws(): void
    {
        $this->expectException(EmbeddingFailedException::class);
        $normalizer = new OpenAIEmbeddingResponseNormalizer;
        $normalizer->normalizeSingle(ChunkData::make('text', 0, 0, 4), 'model', []);
    }

    public function test_openai_normalizer_batch_throws(): void
    {
        $this->expectException(EmbeddingFailedException::class);
        $normalizer = new OpenAIEmbeddingResponseNormalizer;
        $normalizer->normalizeBatch([], 'model', []);
    }

    public function test_openai_adapter_embed_throws(): void
    {
        $this->expectException(EmbeddingFailedException::class);
        $adapter = new OpenAIEmbeddingAdapter(
            new OpenAIClient('url', 'key'),
            new OpenAIEmbeddingResponseNormalizer,
            'model',
        );
        $adapter->embed(ChunkData::make('text', 0, 0, 4));
    }

    public function test_openai_adapter_embed_batch_throws(): void
    {
        $this->expectException(EmbeddingFailedException::class);
        $adapter = new OpenAIEmbeddingAdapter(
            new OpenAIClient('url', 'key'),
            new OpenAIEmbeddingResponseNormalizer,
            'model',
        );
        $adapter->embedBatch([]);
    }

    public function test_openai_adapter_returns_names(): void
    {
        $adapter = new OpenAIEmbeddingAdapter(
            new OpenAIClient('url', 'key'),
            new OpenAIEmbeddingResponseNormalizer,
            'model-name',
        );
        $this->assertSame('openai', $adapter->providerName());
        $this->assertSame('model-name', $adapter->modelName());
    }
}
