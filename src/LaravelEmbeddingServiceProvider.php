<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding;

use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager as EmbeddingManagerContract;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingSearch as EmbeddingSearchContract;
use JOOservices\LaravelEmbedding\Enums\ChunkingStrategy;
use JOOservices\LaravelEmbedding\Enums\EmbeddingProvider as EmbeddingProviderEnum;
use JOOservices\LaravelEmbedding\Exceptions\UnsupportedEmbeddingProviderException;
use JOOservices\LaravelEmbedding\Repositories\EloquentEmbeddingRepository;
use JOOservices\LaravelEmbedding\Services\Chunking\DefaultChunker;
use JOOservices\LaravelEmbedding\Services\Chunking\MarkdownChunker;
use JOOservices\LaravelEmbedding\Services\Chunking\SentenceChunker;
use JOOservices\LaravelEmbedding\Services\Chunking\TokenBudgetChunker;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingManager;
use JOOservices\LaravelEmbedding\Services\Ingestion\ContentNormalizer;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaClient;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaClientInterface;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingAdapter;
use JOOservices\LaravelEmbedding\Services\Providers\Ollama\OllamaEmbeddingResponseNormalizer;
use JOOservices\LaravelEmbedding\Services\Search\EmbeddingSearchService;

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
        $this->registerProviderDependencies();
        $this->registerProvider();
        $this->registerRepository();
        $this->registerSearch();
        $this->app->singleton(ContentNormalizer::class);
        $this->app->singleton(EmbeddingBatchTracker::class);
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

    private function registerProviderDependencies(): void
    {
        $this->app->bind(OllamaClientInterface::class, function (): OllamaClientInterface {
            $config = config('embedding.providers.ollama');

            return new OllamaClient(
                baseUrl: $config['base_url'],
                timeout: $config['timeout'],
            );
        });

        $this->app->singleton(OllamaEmbeddingResponseNormalizer::class);
    }

    private function registerChunker(): void
    {
        $this->app->bind(Chunker::class, function (): Chunker {
            $strategyConfig = config('embedding.chunking.strategy', 'default');
            $strategy = ChunkingStrategy::fromConfig(
                is_string($strategyConfig) ? $strategyConfig : 'default',
            );

            return match ($strategy) {
                ChunkingStrategy::Markdown => new MarkdownChunker,
                ChunkingStrategy::Sentence => new SentenceChunker,
                ChunkingStrategy::Token => new TokenBudgetChunker,
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
                EmbeddingProviderEnum::OpenAI => throw new UnsupportedEmbeddingProviderException(
                    'The [openai] provider is reserved for a future release and is not supported at runtime yet.',
                ),
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

    private function registerSearch(): void
    {
        if (config('embedding.database.enabled', true)) {
            $this->app->bind(EmbeddingSearchContract::class, function (): EmbeddingSearchService {
                return new EmbeddingSearchService(
                    provider: $this->app->make(EmbeddingProvider::class),
                    repository: $this->app->make(EmbeddingRepository::class),
                );
            });
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
                normalizer: $this->app->make(ContentNormalizer::class),
                batchTracker: $this->app->make(EmbeddingBatchTracker::class),
                persistenceEnabled: $persistenceEnabled,
                chunkSize: (int) config('embedding.chunking.chunk_size', 1000),
                chunkOverlap: (int) config('embedding.chunking.chunk_overlap', 100),
                providerBatchSize: (int) config('embedding.batching.size', 0),
                queueConnection: config('embedding.queue.connection'),
                queueName: config('embedding.queue.name'),
                queueTries: (int) config('embedding.queue.tries', 1),
                queueBackoff: config('embedding.queue.backoff', 0),
                queueTimeout: (int) config('embedding.queue.timeout', 120),
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
            client: $this->app->make(OllamaClientInterface::class),
            normalizer: $this->app->make(OllamaEmbeddingResponseNormalizer::class),
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
