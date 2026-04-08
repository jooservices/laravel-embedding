<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Application;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Services\Chunking\MarkdownChunker;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIEmbeddingAdapter;
use JOOservices\LaravelEmbedding\Tests\TestCase;

/**
 * Tests for alternative service provider binding paths
 * (OpenAI provider, Markdown chunker strategy).
 */
final class ServiceProviderBindingsTest extends TestCase
{
    public function test_markdown_chunker_is_bound_when_strategy_is_markdown(): void
    {
        /** @var Application $app */
        $app = $this->app;
        $app['config']->set('embedding.chunking.strategy', 'markdown');
        $app->forgetInstance(Chunker::class);

        $chunker = $app->make(Chunker::class);

        $this->assertInstanceOf(MarkdownChunker::class, $chunker);
    }

    public function test_openai_provider_is_bound_when_configured(): void
    {
        /** @var Application $app */
        $app = $this->app;
        $app['config']->set('embedding.default_provider', 'openai');
        $app['config']->set('embedding.providers.openai', [
            'base_url' => 'https://api.openai.com/v1',
            'api_key' => 'test-key',
            'model' => 'text-embedding-3-small',
            'timeout' => 30,
        ]);

        $app->forgetInstance(EmbeddingProvider::class);

        $provider = $app->make(EmbeddingProvider::class);

        $this->assertInstanceOf(OpenAIEmbeddingAdapter::class, $provider);
    }
}
