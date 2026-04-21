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
    public static function apply(Builder $query, array $vector, ?float $maxDistance = null): Builder
    {
        self::assertValidVector($vector);
        self::assertValidDistance($maxDistance);

        $connection = $query->getConnection();

        if ($connection->getDriverName() !== 'pgsql') {
            throw new RuntimeException(
                'Vector search is only supported on a PostgreSQL database with the pgvector extension.',
            );
        }

        $vectorString = '['.implode(',', $vector).']';

        $query->selectRaw('*, embedding <=> ? AS distance', [$vectorString]);

        if ($maxDistance !== null) {
            $query->whereRaw('embedding <=> ? <= ?', [$vectorString, $maxDistance]);
        }

        return $query->orderByRaw('embedding <=> ?', [$vectorString]);
    }

    /**
     * @param  array<float>  $vector
     */
    private static function assertValidVector(array $vector): void
    {
        if ($vector === []) {
            throw new RuntimeException('Vector search requires a non-empty numeric vector.');
        }

        foreach ($vector as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw new RuntimeException('Vector search requires every vector value to be numeric.');
            }

            if (! is_finite((float) $value)) {
                throw new RuntimeException('Vector search does not support infinite or NaN vector values.');
            }
        }
    }

    private static function assertValidDistance(?float $maxDistance): void
    {
        if ($maxDistance === null) {
            return;
        }

        if ($maxDistance < 0.0 || $maxDistance > 1.0 || ! is_finite($maxDistance)) {
            throw new RuntimeException('Vector search max distance must be a finite value between 0 and 1.');
        }
    }
}
