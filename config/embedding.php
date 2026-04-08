<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Embedding Provider
    |--------------------------------------------------------------------------
    |
    | This option controls which embedding provider is used as default.
    | Supported: "ollama", "openai"
    |
    */
    'default_provider' => env('EMBEDDING_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure each provider used for embedding generation.
    | Each provider may specify a base URL, model, API key, and timeout.
    |
    */
    'providers' => [

        'ollama' => [
            // Your local Ollama instance — keep your existing env var as-is.
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/api'),
            'model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 30),
        ],

        'openai' => [
            // Placeholder for future OpenAI implementation.
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 30),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Chunking Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how raw text is split before being sent to the embedding provider.
    | Overlap allows adjacent chunks to share context at their boundaries.
    | Strategies:
    |   - "default": Fixed character window size with overlap.
    |   - "markdown": Prioritizes splitting at headings/paragraphs before fallback.
    |
    */
    'chunking' => [
        'strategy' => env('EMBEDDING_CHUNK_STRATEGY', 'default'),
        'chunk_size' => (int) env('EMBEDDING_CHUNK_SIZE', 1000),
        'chunk_overlap' => (int) env('EMBEDDING_CHUNK_OVERLAP', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database / Persistence
    |--------------------------------------------------------------------------
    |
    | When enabled, generated embeddings will be persisted to the database.
    | You may configure which connection and table name to use.
    |
    | Note: The default connection is "pgsql" because the package is designed
    | with PostgreSQL (and optionally pgvector) as the primary persistence
    | target. Set to "sqlite" for testing or "mongodb" for document storage.
    |
    */
    'database' => [
        'enabled' => (bool) env('EMBEDDING_DB_ENABLED', true),
        'connection' => env('EMBEDDING_DB_CONNECTION', 'pgsql'),
        'table' => env('EMBEDDING_TABLE', 'embeddings'),
    ],

];
