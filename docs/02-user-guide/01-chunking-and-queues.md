# Usage & Asynchronous Processing

Once the package is installed, you interact primarily through the `Embedding` Facade.

## Background Queues

Generating embeddings over a large PDF (e.g., 50 pages) requires thousands of tokens and multiple network round-trips to the AI Provider, which will block PHP and timeout Nginx.

Always push large data to the background using `queueBatch()`:

```php
use JOOservices\LaravelEmbedding\Facades\Embedding;

$pdfText = $pdfRenderer->extractText();

Embedding::queueBatch($pdfText, [
    'target' => $postModel, // Saves to `embeddable_type` and `embeddable_id`
    'title'  => 'Annual Report 2024' // Saves to JSON `meta` column
]);
```
This safely dispatches a `ProcessEmbeddingBatchJob` into your default Laravel Queue worker.

## Vector Search (Nearest Neighborhood)

To perform Retrieval (the "R" in RAG), you need to convert the user's question into a vector, and then ask the database to sort by distance:

```php
$question = "What was our revenue in Q3?";
$qVector = Embedding::embedText($question)->vector;

$chunks = \JOOservices\LaravelEmbedding\Models\Embedding::query()
    ->where('embeddable_type', \App\Models\Post::class) // Optional scope constraints
    ->nearestTo($qVector) // Converts to `<=>` distance operator in PostgreSQL
    ->limit(3)
    ->get();

foreach ($chunks as $chunk) {
    echo $chunk->content; // Inject this into your LLM Prompt payload!
}
```

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

When you call `$document->delete()`, the trait automatically destroys all related records in the `embeddings` table.
