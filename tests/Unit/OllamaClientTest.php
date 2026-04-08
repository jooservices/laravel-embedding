<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaClient;
use JOOservices\LaravelEmbedding\Tests\TestCase;

/**
 * Tests for OllamaClient HTTP behaviour at the transport layer.
 */
final class OllamaClientTest extends TestCase
{
    private OllamaClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new OllamaClient('http://localhost:11434/api', 5);
    }

    public function test_embed_returns_response_on_success(): void
    {
        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([
                'model' => 'nomic-embed-text',
                'embeddings' => [[0.1, 0.2, 0.3]],
            ], 200),
        ]);

        $result = $this->client->embed(['model' => 'nomic-embed-text', 'input' => 'hello']);

        $this->assertArrayHasKey('embeddings', $result);
    }

    public function test_embed_throws_on_http_error(): void
    {
        $this->expectException(EmbeddingFailedException::class);

        Http::fake([
            'http://localhost:11434/api/embed' => Http::response('Server Error', 500),
        ]);

        $this->client->embed(['model' => 'nomic-embed-text', 'input' => 'hello']);
    }

    public function test_embed_throws_on_connection_exception(): void
    {
        $this->expectException(EmbeddingFailedException::class);
        $this->expectExceptionMessage('Connection failed');

        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->client->embed(['model' => 'nomic-embed-text', 'input' => 'hello']);
    }
}
