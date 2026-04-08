<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests;

use JOOservices\LaravelEmbedding\LaravelEmbeddingServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Register the package service provider with the test application.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelEmbeddingServiceProvider::class,
        ];
    }

    /**
     * Define environment setup for tests.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Default to SQLite in-memory for speed and isolation.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Point the embedding package to sqlite for most tests.
        $app['config']->set('embedding.database.connection', 'sqlite');
        $app['config']->set('embedding.database.table', 'embeddings');
        $app['config']->set('embedding.database.enabled', true);

        // Chunking defaults.
        $app['config']->set('embedding.chunking.chunk_size', 100);
        $app['config']->set('embedding.chunking.chunk_overlap', 10);

        // Ollama defaults.
        $app['config']->set('embedding.default_provider', 'ollama');
        $app['config']->set('embedding.providers.ollama', [
            'base_url' => 'http://localhost:11434/api',
            'model' => 'nomic-embed-text',
            'timeout' => 30,
        ]);
    }
}
