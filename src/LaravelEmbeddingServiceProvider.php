<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding;

use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager as EmbeddingManagerContract;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\Enums\EmbeddingProvider as EmbeddingProviderEnum;
use JOOservices\LaravelEmbedding\Repositories\EloquentEmbeddingRepository;
use JOOservices\LaravelEmbedding\Services\Chunking\DefaultChunker;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingManager;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaClient;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingAdapter;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingResponseNormalizer;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIClient;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIEmbeddingAdapter;
use JOOservices\LaravelEmbedding\Services\Providers\OpenAI\OpenAIEmbeddingResponseNormalizer;

final class LaravelEmbeddingServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings into the service container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/embedding.php',
            'embedding',
        );

        $this->registerChunker();
        $this->registerProvider();
        $this->registerRepository();
        $this->registerManager();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->publishMigrations();
        }

        $this->loadMigrationsFrom(
            __DIR__.'/../database/migrations',
        );
    }

    // -------------------------------------------------------------------------
    // Private registration methods
    // -------------------------------------------------------------------------

    private function registerChunker(): void
    {
        $this->app->bind(Chunker::class, function (): Chunker {
            $strategy = config('embedding.chunking.strategy', 'default');

            return match ($strategy) {
                'markdown' => new Services\Chunking\MarkdownChunker,
                default => new DefaultChunker,
            };
        });
    }

    private function registerProvider(): void
    {
        $this->app->bind(EmbeddingProvider::class, function (): EmbeddingProvider {
            $providerKey = config('embedding.default_provider', 'ollama');
            $provider = EmbeddingProviderEnum::fromConfig($providerKey);

            return match ($provider) {
                EmbeddingProviderEnum::Ollama => $this->makeOllamaAdapter(),
                EmbeddingProviderEnum::OpenAI => $this->makeOpenAIAdapter(),
            };
        });
    }

    private function registerRepository(): void
    {
        // The repository is only bound when persistence is enabled.
        // EmbeddingManager receives null otherwise.
        if (config('embedding.database.enabled', true)) {
            $this->app->bind(EmbeddingRepository::class, EloquentEmbeddingRepository::class);
        }
    }

    private function registerManager(): void
    {
        $this->app->bind(EmbeddingManagerContract::class, function (): EmbeddingManager {
            $persistenceEnabled = (bool) config('embedding.database.enabled', true);
            $repository = $persistenceEnabled
                ? $this->app->make(EmbeddingRepository::class)
                : null;

            return new EmbeddingManager(
                chunker: $this->app->make(Chunker::class),
                provider: $this->app->make(EmbeddingProvider::class),
                repository: $repository,
                persistenceEnabled: $persistenceEnabled,
                chunkSize: (int) config('embedding.chunking.chunk_size', 1000),
                chunkOverlap: (int) config('embedding.chunking.chunk_overlap', 100),
            );
        });
    }

    // -------------------------------------------------------------------------
    // Provider factory methods
    // -------------------------------------------------------------------------

    private function makeOllamaAdapter(): OllamaEmbeddingAdapter
    {
        $config = config('embedding.providers.ollama');

        return new OllamaEmbeddingAdapter(
            client: new OllamaClient(
                baseUrl: $config['base_url'],
                timeout: $config['timeout'],
            ),
            normalizer: new OllamaEmbeddingResponseNormalizer,
            model: $config['model'],
        );
    }

    private function makeOpenAIAdapter(): OpenAIEmbeddingAdapter
    {
        $config = config('embedding.providers.openai');

        return new OpenAIEmbeddingAdapter(
            client: new OpenAIClient(
                baseUrl: $config['base_url'],
                apiKey: $config['api_key'] ?? '',
                timeout: $config['timeout'],
            ),
            normalizer: new OpenAIEmbeddingResponseNormalizer,
            model: $config['model'],
        );
    }

    // -------------------------------------------------------------------------
    // Publishing
    // -------------------------------------------------------------------------

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/embedding.php' => config_path('embedding.php'),
        ], 'embedding-config');
    }

    private function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'embedding-migrations');
    }
}
