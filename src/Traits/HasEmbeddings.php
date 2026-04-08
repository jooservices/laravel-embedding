<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use JOOservices\LaravelEmbedding\Models\Embedding;
use RuntimeException;

/**
 * Provides an Eloquent relationship and automatic lifecycle management for embeddings.
 * Attach this to any model that generates embeddings (e.g., `Document`, `Post`).
 */
trait HasEmbeddings
{
    /**
     * Get all embeddings associated with this model.
     */
    public function embeddings(): MorphMany
    {
        return $this->morphMany(Embedding::class, 'embeddable');
    }

    /**
     * Boot the trait to hook into the model lifecycle events.
     */
    protected static function bootHasEmbeddings(): void
    {
        static::deleting(function (self $model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->embeddings()->delete();
        });
    }

    /**
     * Queue a background job to fully re-embed this model's content.
     * You must implement `getEmbeddableContent()` on your model to use this.
     *
     * @param  array<string, mixed>  $meta
     */
    public function queueEmbedding(array $meta = []): void
    {
        if (! method_exists($this, 'getEmbeddableContent')) {
            throw new RuntimeException('Model must implement getEmbeddableContent() to use queueEmbedding.');
        }

        $text = $this->getEmbeddableContent();

        if (trim($text) === '') {
            return;
        }

        // Dispatch the fan-out batch generation flow. When replacement is
        // requested, the target is cleared once before chunk jobs are enqueued.
        $context = array_merge($meta, [
            'target' => $this,
            'replace_existing' => true,
            'skip_if_unchanged' => true,
        ]);
        \JOOservices\LaravelEmbedding\Facades\Embedding::queueBatch($text, $context);
    }
}
