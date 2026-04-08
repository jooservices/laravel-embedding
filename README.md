# JOOservices Laravel Embedding

A production-grade, enterprise-ready vector embedding and chunking package for Laravel 11+.
Designed to empower Retrieval-Augmented Generation (RAG) applications using Ollama (and soon OpenAI) with robust Background Queueing, Smart Text Chunking, and native PostgreSQL `pgvector` nearest-neighbor search.

## Key Features

1. **Smart Context Chunking:** Word-boundary protection to prevent semantic clipping. Includes `DefaultChunker` and `MarkdownChunker` for paragraph/heading aware splitting.
2. **Native Vector Search:** Seamlessly uses `pgvector` distance operators (`<=>`) over indexed columns for lightning-fast retrieval. Fallback to `json` on SQLite/MySQL.
3. **Background Processing:** Ships with Laravel Jobs to dispatch massive documents asynchronously without blocking the main HTTP request thread.
4. **Lifecycle Syncing:** Attaching `HasEmbeddings` trait ensures embeddings are automatically wiped / soft-deleted when their parent Eloquent model is destroyed.
5. **Dynamic Configuration:** Easily inject context variables (`chunk_size`, `chunk_overlap`) at runtime to override `.env` limits on a per-request basis.

## Quick Start

Please read the complete documentation available in the `docs/` directory:

- [Installation & Setup](docs/installation.md)
- [Managing DB / pgvector](docs/database.md)
- [Using Chunks & Enqueuing](docs/usage.md)
- [Dynamic Runtime Context](docs/context.md)

## Basic Usage

```php
use JOOservices\LaravelEmbedding\Facades\Embedding;

// 1. Single text raw vector
$vector = Embedding::embedText('Who is the CEO of Apple?');

// 2. Heavy PDF Chunk & Embed
Embedding::chunkAndEmbed($hugePdfContent, [
    'target' => $documentModel, // Polymorphic binding
    'author' => 'System'        // Saved to Meta JSON
]);

// 3. Search & Retrieve (pgvector)
$searchVector = Embedding::embedText('Company leadership')->vector;

$results = \JOOservices\LaravelEmbedding\Models\Embedding::query()
    ->nearestTo($searchVector)
    ->limit(5)
    ->get();
```

## AI Agents & Development

This package contains strict documentation for external AI Agents (Cursor, Cline, Github Copilot).
If you are an AI Agent building on top of this package, read the Skill sheet located at `.agents/skills/laravel-embedding/SKILL.md`.
