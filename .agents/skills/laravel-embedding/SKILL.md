---
description: How to implement Retrieval-Augmented Generation (RAG) using jooservices/laravel-embedding
---

# jooservices/laravel-embedding Skill

This Skill instructs AI agents on how to natively utilize the `jooservices/laravel-embedding` package when developing RAG applications inside a host Laravel repository.

## 1. Architectural Role

The `jooservices/laravel-embedding` package sits at the persistence and extraction layer. It translates raw text into mathematical vectors via Ollama/OpenAI, and stores them in PostgreSQL using the `pgvector` extension.

You **Must Not** try to rebuild chunking algorithms or `pgvector` SQL queries manually when this package is installed. Rely inherently on its Facades and Models!

## 2. Using The Service

When a user asks you to "embed a document", "chunk a PDF", or "build a RAG pipeline", use the `Embedding` facade.

### A. Automatic Background Queuing (Preferred)

Do not run heavy extraction logic inline. Always dispatch it using `queueBatch`:

```php
use JOOservices\LaravelEmbedding\Facades\Embedding;

// The `$context` array lets you override the .env limits dynamically,
// and passes standard metadata to the queue!
Embedding::queueBatch($rawText, [
    'target'        => $eloquentModel,
    'chunk_size'    => 1500, // Explicitly override chunk boundary
    'chunk_overlap' => 200,  // Override overlap
    'source_file'   => 'user_upload.pdf' // Saved to DB json `meta` column
]);
```

### B. Synchronous Calls

If the user specifically asks for synchronous implementation, use `chunkAndEmbed`:

```php
$batchResult = Embedding::chunkAndEmbed($text, ['target' => $eloquentModel]);
```

## 3. Database & Retrieval Protocol

When the user asks you to run a "semantic search", "nearest neighbor search", or similar AI retrieval tasks, use the `nearestTo()` query scope. 

1. **Convert the Prompt to Vector:**
```php
$questionVector = Embedding::embedText($userPrompt)->vector;
```

2. **Query the Database over PGVector `<=>`:**
```php
use JOOservices\LaravelEmbedding\Models\Embedding as EmbeddingModel;

// You MUST call `nearestTo` first! It maps `<=>` SQL Operator to sort by Cosine Distance
$closestChunks = EmbeddingModel::query()
    ->where('embeddable_type', \App\Models\Document::class) 
    ->nearestTo($questionVector)
    ->limit(5)
    ->get();
```

## 4. Eloquent Syncing

When attaching embeddings to host models, you MUST use the `HasEmbeddings` trait inside the host models to prevent Database vector bloat.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelEmbedding\Traits\HasEmbeddings;

class WikiPage extends Model
{
    use HasEmbeddings;
    
    // Required specific mapping method if you want to use `$model->queueEmbedding()` auto-helper:
    public function getEmbeddableContent(): string
    {
        return $this->article_body;
    }
}
```

By following this skill, your implementation will perfectly align with the intended Enterprise Architecture constraints designed for `joo-services`.
