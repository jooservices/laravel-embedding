# JOOservices Laravel Embedding Library

A Laravel package for text chunking, Ollama-based embedding generation, optional persistence, and PostgreSQL `pgvector` similarity search.

Current runtime support is intentionally narrow:

- Ollama embedding generation is supported.
- PostgreSQL with `pgvector` is required for similarity search.
- SQLite/MySQL can persist vectors, but they do not provide vector search through this package.
- OpenAI configuration is reserved for a future release and is not supported at runtime yet.

## Key Features

1. **Smart Context Chunking:** Includes `DefaultChunker`, `MarkdownChunker`, `SentenceChunker`, and `TokenBudgetChunker`.
2. **Native PostgreSQL Vector Search:** Uses `pgvector` cosine-distance operators (`<=>`) when your embedding store is PostgreSQL.
3. **Background Processing:** Ships with queue-aware jobs plus configurable queue connection, queue name, retry/backoff, timeout, and overlap protection.
4. **Safer Re-Embedding:** Can skip unchanged targets and replace persisted target sets only after successful generation.
5. **Flexible Targeting:** Supports Eloquent-backed targets and non-Eloquent `target_type` / `target_id` references.
6. **Search Helpers:** Supports metadata-aware filtering and a thin `EmbeddingSearch` service.

## Quick Start

Please read the complete documentation available in the `docs/` directory:

- [Installation & Setup](docs/01-getting-started/installation.md)
- [Usage & Asynchronous Processing](docs/02-user-guide/01-chunking-and-queues.md)
- [PostgreSQL pgvector Performance](docs/02-user-guide/02-pgvector-performance.md)
- [Dynamic Runtime Context](docs/03-examples/runtime-context.md)

## Basic Usage

```php
use JOOservices\LaravelEmbedding\Facades\Embedding;
use JOOservices\LaravelEmbedding\Facades\EmbeddingSearch;

// 1. Single text raw vector
$vector = Embedding::embedText('Who is the CEO of Apple?');

// 2. Chunk, embed, and persist a non-Eloquent target
Embedding::chunkAndEmbed($hugePdfContent, [
    'target_type' => 'document',
    'target_id' => 'annual-report-2024',
    'namespace' => 'finance',
    'skip_if_unchanged' => true,
    'author' => 'System',
]);

// 3. Search & Retrieve (PostgreSQL + pgvector only)
$results = EmbeddingSearch::similarToText('Company leadership', 5, [
    'namespace' => 'finance',
    'meta' => ['author' => 'System'],
]);
```

## PostgreSQL Notes

This package does not auto-create a pgvector ANN index because index strategy depends on your chosen model dimensions and operational preferences. Treat extension enablement and index creation as deployment decisions in the host application.

If you want the package migration to attempt `CREATE EXTENSION vector`, enable:

```env
EMBEDDING_PGVECTOR_ENSURE_EXTENSION=true
```

## AI Agents & Development

This package contains strict documentation for external AI Agents (Cursor, Cline, Github Copilot).
If you are an AI Agent building on top of this package, read the Skill sheet located at `.agents/skills/laravel-embedding/SKILL.md`.
