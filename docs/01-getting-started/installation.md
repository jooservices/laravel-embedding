# Installation & Setup

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
```

> **Warning:** To exploit full Vector Search (Retrieval), your `EMBEDDING_DB_CONNECTION` must point to a PostgreSQL connection running the `pgvector` extension.
