<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use JOOservices\LaravelEmbedding\Support\PgvectorSimilarityQuery;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;
use RuntimeException;

final class PgvectorSimilarityQueryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_apply_throws_for_non_postgresql_connections(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Vector search is only supported on a PostgreSQL database');

        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('getConnection')->once()->andReturn($connection);

        PgvectorSimilarityQuery::apply($query, [0.1, 0.2]);
    }

    public function test_apply_adds_distance_selection_and_ordering_for_postgresql(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('pgsql');

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('getConnection')->once()->andReturn($connection);
        $query->shouldReceive('selectRaw')
            ->once()
            ->with('*, embedding <=> ? AS distance', ['[0.1,0.2]'])
            ->andReturnSelf();
        $query->shouldReceive('orderByRaw')
            ->once()
            ->with('embedding <=> ?', ['[0.1,0.2]'])
            ->andReturnSelf();

        $result = PgvectorSimilarityQuery::apply($query, [0.1, 0.2]);

        $this->assertSame($query, $result);
    }
}
