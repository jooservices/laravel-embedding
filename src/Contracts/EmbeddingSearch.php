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

    /**
     * @param  array<string, mixed>  $filters
     */
    public function similarToTextInNamespace(string $text, string $namespace, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;

    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>  $filters
     */
    public function similarToVectorInNamespace(array $vector, string $namespace, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function similarToTextAboveScore(string $text, float $minScore, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;

    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>  $filters
     */
    public function similarToVectorAboveScore(array $vector, float $minScore, int $limit = 5, array $filters = []): \Illuminate\Support\Collection;
}
