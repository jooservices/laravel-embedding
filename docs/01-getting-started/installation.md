# JOOservices Laravel Embedding Library Installation & Setup

Require the package via Composer:

```bash
composer require jooservices/laravel-embedding
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag="embedding-config"
php artisan vendor:publish --tag="embedding-migrations"
```

## Environment Variables

At the root of your Laravel application `.env`, you must configure your Provider.

```env
EMBEDDING_PROVIDER=ollama
OLLAMA_BASE_URL=http://localhost:11434/api
OLLAMA_EMBEDDING_MODEL=nomic-embed-text

EMBEDDING_CHUNK_STRATEGY=markdown
EMBEDDING_CHUNK_SIZE=1500
EMBEDDING_CHUNK_OVERLAP=150

EMBEDDING_DB_ENABLED=true
EMBEDDING_DB_CONNECTION=pgsql
EMBEDDING_PGVECTOR_ENSURE_EXTENSION=false
```

## Support Matrix

- Ollama embedding generation: supported
- PostgreSQL + `pgvector` similarity search: supported
- SQLite/MySQL persistence without vector search: supported for storage only
- OpenAI provider: reserved for a future release, not supported at runtime yet

## PostgreSQL Requirements

To use nearest-neighbour search, `EMBEDDING_DB_CONNECTION` must point to a PostgreSQL connection with the `pgvector` extension available.

The package migration will only attempt `CREATE EXTENSION vector` when `EMBEDDING_PGVECTOR_ENSURE_EXTENSION=true`. Leave it `false` when extension management is handled outside Laravel migrations, which is common in managed PostgreSQL environments.
