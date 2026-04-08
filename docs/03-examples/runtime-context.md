# Dynamic Context & Overrides

This package allows you to manipulate Chunking sizes and other meta-data on the fly without mutating global Laravel configurations. This is incredibly useful if you have mixed workloads.

## The Context Array

Any function on the `Embedding` facade accepts an optional `$context` array.

```php
$context = [
    'target'        => $eloquentModel, // Automaps polymorphically
    'chunk_size'    => 2000,           // Overrides EMBEDDING_CHUNK_SIZE
    'chunk_overlap' => 300,            // Overrides EMBEDDING_CHUNK_OVERLAP
    'lang'          => 'vi',           // Pushed to JSON meta
    'author_id'     => 1               // Pushed to JSON meta
];

Embedding::queueBatch($heavyText, $context);
```

### Background Persistence

Because this `$context` array is serialized and passed securely down into the `ProcessEmbeddingBatchJob`, the runtime adjustments you apply will safely survive Queue delays and operate correctly in background workers!
