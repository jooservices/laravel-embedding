<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

interface EmbeddingSearch
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function similarToText(string $text, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;

    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>  $filters
     */
    public function similarToVector(array $vector, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;
}
