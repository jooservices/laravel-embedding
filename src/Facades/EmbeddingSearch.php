<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection similarToText(string $text, int $limit = 5, array $filters = [])
 * @method static \Illuminate\Support\Collection similarToVector(array $vector, int $limit = 5, array $filters = [])
 */
final class EmbeddingSearch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JOOservices\LaravelEmbedding\Contracts\EmbeddingSearch::class;
    }
}
