<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

/**
 * Eloquent model representing a persisted embedding record.
 *
 * The database connection and table name are driven entirely by package
 * configuration, making this model portable across any host application.
 *
 * @property int $id
 * @property string|null $embeddable_type
 * @property int|string|null $embeddable_id
 * @property string $provider
 * @property string $model
 * @property int $dimension
 * @property int $chunk_index
 * @property string $content
 * @property string $content_hash
 * @property array $embedding
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Embedding extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'embeddable_type',
        'embeddable_id',
        'provider',
        'model',
        'dimension',
        'chunk_index',
        'content',
        'content_hash',
        'embedding',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'dimension' => 'integer',
        'chunk_index' => 'integer',
        'embedding' => 'array',
        'meta' => 'array',
    ];

    /**
     * Resolve the database connection from package config.
     */
    public function getConnectionName(): string
    {
        return config('embedding.database.connection', 'pgsql');
    }

    /**
     * Resolve the table name from package config.
     */
    public function getTable(): string
    {
        return config('embedding.database.table', 'embeddings');
    }

    /**
     * Polymorphic relationship to the owning model.
     * Nullable — embeddings may exist without a bound Eloquent target.
     */
    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Search scope to order records by Vector Cosine Distance.
     * Uses pgvector's <=> operator.
     *
     * @param  array<float>  $vector
     */
    public function scopeNearestTo(Builder $query, array $vector): Builder
    {
        $connection = $query->getConnection();

        if ($connection->getDriverName() === 'pgsql') {
            $vectorString = '['.implode(',', $vector).']';

            return $query->selectRaw('*, embedding <=> ? AS distance', [$vectorString])
                ->orderByRaw('embedding <=> ?', [$vectorString]);
        }

        throw new RuntimeException('Vector search is only supported on a PostgreSQL database with the pgvector extension.');
    }
}
