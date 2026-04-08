<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Support;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final class PgvectorSimilarityQuery
{
    /**
     * @param  array<float>  $vector
     */
    public static function apply(Builder $query, array $vector): Builder
    {
        $connection = $query->getConnection();

        if ($connection->getDriverName() !== 'pgsql') {
            throw new RuntimeException(
                'Vector search is only supported on a PostgreSQL database with the pgvector extension.',
            );
        }

        $vectorString = '['.implode(',', $vector).']';

        return $query->selectRaw('*, embedding <=> ? AS distance', [$vectorString])
            ->orderByRaw('embedding <=> ?', [$vectorString]);
    }
}
