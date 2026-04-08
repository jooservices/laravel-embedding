# Dynamic Context & Overrides

This package allows you to manipulate Chunking sizes and other meta-data on the fly without mutating global Laravel configurations. This is incredibly useful if you have mixed workloads.

## The Context Array

Any function on the `Embedding` facade accepts an optional `$context` array.

```php
$context = [
    'target'        => $eloquentModel, // Automaps polymorphically
    'chunk_size'    => 2000,           // Overrides EMBEDDING_CHUNK_SIZE
    'chunk_overlap' => 300,            // Overrides EMBEDDING_CHUNK_OVERLAP
    'batch_size'    => 32,             // Splits provider batch calls into smaller requests
    'replace_existing' => true,        // queueChunked() clears the target once before chunk fan-out
    'skip_if_unchanged' => true,       // queueChunked() skips dispatch when stored chunk hashes already match
    'queue_name'    => 'embeddings',   // Overrides queue target for queueBatch()
    'queue_timeout' => 180,            // Overrides job timeout in seconds
    'lang'          => 'vi',           // Pushed to JSON meta
    'author_id'     => 1               // Pushed to JSON meta
];

Embedding::queueBatch($heavyText, $context);
```

For non-Eloquent content, replace `target` with `target_type`, `target_id`, and optional `namespace`.

### Background Persistence

Because this `$context` array is serialized and passed down into the `ProcessEmbeddingBatchJob`, the runtime adjustments you apply survive queue delays and operate consistently in background workers. In the fan-out path, `ProcessEmbeddingBatchJob` performs the initial chunking and then dispatches one `ProcessChunkJob` per chunk.
