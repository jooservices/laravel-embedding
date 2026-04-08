<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use JOOservices\LaravelEmbedding\Support\PgvectorSimilarityQuery;
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
 * @property string|null $target_type
 * @property int|string|null $target_id
 * @property string $provider
 * @property string $model
 * @property int $dimension
 * @property int $chunk_index
 * @property string $content
 * @property string $content_hash
 * @property string|null $batch_token
 * @property bool $is_active
 * @property array $embedding
 * @property float|null $distance
 * @property string|null $namespace
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
        'target_type',
        'target_id',
        'provider',
        'model',
        'dimension',
        'chunk_index',
        'content',
        'content_hash',
        'batch_token',
        'is_active',
        'embedding',
        'namespace',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'dimension' => 'integer',
        'chunk_index' => 'integer',
        'target_id' => 'string',
        'is_active' => 'boolean',
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
        return PgvectorSimilarityQuery::apply($query, $vector);
    }

    public function scopeForTarget(Builder $query, string $targetType, int|string|null $targetId = null): Builder
    {
        $query->where('target_type', $targetType);

        if ($targetId !== null) {
            $query->where('target_id', (string) $targetId);
        }

        return $query;
    }

    public function scopeForProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForModel(Builder $query, string $model): Builder
    {
        return $query->where('model', $model);
    }

    public function scopeInNamespace(Builder $query, string $namespace): Builder
    {
        return $query->where('namespace', $namespace);
    }

    public function scopeWithMetaFilter(Builder $query, string $key, mixed $value): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            if ($value === null) {
                return $query->whereRaw('meta ->> ? IS NULL', [$key]);
            }

            if (is_scalar($value)) {
                return $query->whereRaw('meta ->> ? = ?', [$key, (string) $value]);
            }

            throw new RuntimeException('Metadata filtering only supports scalar or null values.');
        }

        if (in_array($driver, ['sqlite', 'mysql'], true)) {
            return $query->where("meta->{$key}", $value);
        }

        throw new RuntimeException('Metadata filtering is not supported on the configured database driver.');
    }
}
