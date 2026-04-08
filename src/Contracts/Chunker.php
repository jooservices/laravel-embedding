<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Contracts;

use JOOservices\LaravelEmbedding\DTOs\ChunkData;

interface Chunker
{
    /**
     * Split the given text into an ordered array of ChunkData objects.
     *
     * @param  string  $text  The raw input text to be chunked.
     * @param  int  $size  Target size of each chunk in characters.
     * @param  int  $overlap  Number of overlapping characters between consecutive chunks.
     * @return ChunkData[]
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\ChunkingException
     */
    public function chunk(string $text, int $size, int $overlap): array;
}
