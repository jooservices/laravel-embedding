# PostgreSQL pgvector Performance

The JOOservices Laravel Embedding Library stores vectors in PostgreSQL using `pgvector` when the configured database connection is `pgsql`. Similarity search orders records with the cosine-distance operator `<=>`.

The package migration creates general lookup indexes for targets, providers, models, active rows, and batch tokens. It does not create an approximate nearest-neighbour index automatically because the best index depends on your embedding model dimension, table size, PostgreSQL version, and write pattern.

## Before Adding ANN Indexes

Confirm these points first:

- `EMBEDDING_DB_CONNECTION` points to a PostgreSQL connection.
- The `vector` extension exists in the database.
- Your embedding model dimension is stable.
- Your search queries include useful filters such as `provider`, `model`, `namespace`, `target_type`, or `is_active`.

For small tables, a sequential scan ordered by `<=>` may be acceptable. Add an ANN index when the table grows enough that search latency matters.

## HNSW Index

HNSW is usually the best default for read-heavy production search. It has stronger recall and does not require a training phase, but it uses more memory and makes writes heavier.

Create the index in a host application migration after you know the vector column dimension:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX embeddings_embedding_hnsw_idx ON embeddings USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS embeddings_embedding_hnsw_idx');
    }
};
```

Use HNSW when:

- Search traffic is high.
- You need good recall.
- You can afford extra index memory.
- Writes are not overwhelmingly more important than reads.

## IVFFlat Index

IVFFlat can be lighter than HNSW, but it should be created after the table already contains representative data.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX embeddings_embedding_ivfflat_idx ON embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS embeddings_embedding_ivfflat_idx');
    }
};
```

Use IVFFlat when:

- The table already has enough representative rows before index creation.
- You want a smaller or simpler ANN index.
- You can tune `lists` and query probes for your dataset.

## Filter Indexes

Most RAG searches should filter before ordering by vector distance. The package migration already includes target and active lookup indexes, but applications with strong tenancy or namespace boundaries may benefit from additional partial indexes.

Example for active rows by namespace/provider/model:

```php
DB::statement(
    'CREATE INDEX embeddings_active_namespace_provider_model_idx
     ON embeddings (namespace, provider, model)
     WHERE is_active = true'
);
```

## Operational Notes

- Keep `provider` and `model` filters in search calls when multiple embedding models share the table.
- Do not mix vector dimensions for the same searchable dataset.
- Rebuild or add model-specific indexes when changing embedding models.
- Measure with `EXPLAIN (ANALYZE, BUFFERS)` in the host application database.
- SQLite and MySQL can store vectors through this package, but they do not support similarity search.
