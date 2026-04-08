<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Application;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingSearch;
use JOOservices\LaravelEmbedding\Exceptions\UnsupportedEmbeddingProviderException;
use JOOservices\LaravelEmbedding\Services\Chunking\MarkdownChunker;
use JOOservices\LaravelEmbedding\Services\Chunking\SentenceChunker;
use JOOservices\LaravelEmbedding\Services\Chunking\TokenBudgetChunker;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaClientInterface;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingResponseNormalizer;
use JOOservices\LaravelEmbedding\Services\Search\EmbeddingSearchService;
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

    public function test_sentence_chunker_is_bound_when_strategy_is_sentence(): void
    {
        /** @var Application $app */
        $app = $this->app;
        $app['config']->set('embedding.chunking.strategy', 'sentence');
        $app->forgetInstance(Chunker::class);

        $chunker = $app->make(Chunker::class);

        $this->assertInstanceOf(SentenceChunker::class, $chunker);
    }

    public function test_token_chunker_is_bound_when_strategy_is_token(): void
    {
        /** @var Application $app */
        $app = $this->app;
        $app['config']->set('embedding.chunking.strategy', 'token');
        $app->forgetInstance(Chunker::class);

        $chunker = $app->make(Chunker::class);

        $this->assertInstanceOf(TokenBudgetChunker::class, $chunker);
    }

    public function test_ollama_provider_dependencies_are_resolvable(): void
    {
        $client = $this->app->make(OllamaClientInterface::class);
        $normalizer = $this->app->make(OllamaEmbeddingResponseNormalizer::class);

        $this->assertInstanceOf(OllamaClientInterface::class, $client);
        $this->assertSame($normalizer, $this->app->make(OllamaEmbeddingResponseNormalizer::class));
    }

    public function test_openai_provider_throws_explicitly_when_configured(): void
    {
        $this->expectException(UnsupportedEmbeddingProviderException::class);

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
        $app->make(EmbeddingProvider::class);
    }

    public function test_search_service_is_bound_when_persistence_is_enabled(): void
    {
        $search = $this->app->make(EmbeddingSearch::class);

        $this->assertInstanceOf(EmbeddingSearchService::class, $search);
    }
}
