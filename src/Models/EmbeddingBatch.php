<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $target_type
 * @property string|null $target_id
 * @property string|null $namespace
 * @property string|null $provider
 * @property string|null $model
 * @property string $status
 * @property int $total_chunks
 * @property int $completed_chunks
 * @property int $failed_chunks
 * @property bool $replace_existing
 * @property bool $skip_if_unchanged
 * @property string|null $staged_batch_token
 * @property string|null $source_format
 * @property string|null $source_path
 * @property string|null $summary
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class EmbeddingBatch extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    /** @var array<int, string> */
    protected $fillable = [
        'id',
        'target_type',
        'target_id',
        'namespace',
        'provider',
        'model',
        'status',
        'total_chunks',
        'completed_chunks',
        'failed_chunks',
        'replace_existing',
        'skip_if_unchanged',
        'staged_batch_token',
        'source_format',
        'source_path',
        'summary',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_chunks' => 'integer',
        'completed_chunks' => 'integer',
        'failed_chunks' => 'integer',
        'replace_existing' => 'boolean',
        'skip_if_unchanged' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function getConnectionName(): string
    {
        return config('embedding.database.connection', 'pgsql');
    }

    public function getTable(): string
    {
        return config('embedding.database.batch_table', 'embedding_batches');
    }
}
