# Usage & Asynchronous Processing

Once the package is installed, you interact primarily through the `Embedding` Facade.

Supported chunking strategies today:

- `default`
- `markdown`
- `sentence`
- `token`

## Background Queues

Generating embeddings over a large PDF (e.g., 50 pages) requires thousands of tokens and multiple network round-trips to the AI Provider, which will block PHP and timeout Nginx.

Always push large data to the background using `queueBatch()`:

```php
use JOOservices\LaravelEmbedding\Facades\Embedding;

$pdfText = $pdfRenderer->extractText();

Embedding::queueBatch($pdfText, [
    'target' => $postModel, // Saves to `embeddable_type` and `embeddable_id`
    'replace_existing' => true,
    'skip_if_unchanged' => true,
    'queue_name' => 'embeddings',
    'queue_timeout' => 180,
    'batch_size' => 32,
    'title'  => 'Annual Report 2024' // Saves to JSON `meta` column
]);
```
This dispatches a `ProcessEmbeddingBatchJob`, which chunks the text and fans out one `ProcessChunkJob` per chunk for parallel embedding work. You can also configure default queue connection, queue name, tries, backoff, timeout, and provider batch size in `config/embedding.php`.

When `skip_if_unchanged` is enabled, the batch job compares the incoming chunk hashes with the stored target set and skips dispatching chunk jobs when the hashes already match.

When `replace_existing` is enabled in the fan-out flow, the batch job clears the target once before chunk jobs are dispatched so parallel workers do not delete each other's writes. This means old vectors are not kept around while the replacement chunk jobs are still running.

## Non-Eloquent Targets

If your content source is not an Eloquent model, you can persist against a package-level target reference:

```php
Embedding::chunkAndEmbed($pdfText, [
    'target_type' => 'document',
    'target_id' => 'report-2024',
    'namespace' => 'finance',
    'skip_if_unchanged' => true,
]);
```

## Vector Search (PostgreSQL Only)

To perform Retrieval (the "R" in RAG), you need to convert the user's question into a vector, and then ask the database to sort by distance:

```php
$question = "What was our revenue in Q3?";
$qVector = Embedding::embedText($question)->vector;

$chunks = \JOOservices\LaravelEmbedding\Models\Embedding::query()
    ->where('embeddable_type', \App\Models\Post::class) // Optional scope constraints
    ->nearestTo($qVector) // Uses pgvector `<=>` distance on PostgreSQL
    ->limit(3)
    ->get();

foreach ($chunks as $chunk) {
    echo $chunk->content; // Inject this into your LLM Prompt payload!
}
```

For a thinner package API, resolve the search service and pass filters there instead of querying the model directly.

## Auto-cleanup with Eloquent

To avoid "orphan" vectors polluting your database, attach `HasEmbeddings` to your application's Source Eloquent models:

```php
use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelEmbedding\Traits\HasEmbeddings;

class UserDocument extends Model
{
    use HasEmbeddings;
    
    // Required if you plan to use $document->queueEmbedding();
    public function getEmbeddableContent(): string
    {
        return $this->body_text;
    }
}
```

When you call `$document->queueEmbedding()`, the package dispatches the fan-out batch flow with `replace_existing` and `skip_if_unchanged` enabled. Automatic cleanup on model removal only happens on force-delete.
