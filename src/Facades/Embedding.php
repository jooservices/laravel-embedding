<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Facades;

use Illuminate\Support\Facades\Facade;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;

/**
 * Facade providing static access to the EmbeddingManager service.
 *
 * @method static EmbeddingVectorData embedText(string $text, array $context = [])
 * @method static EmbeddingBatchResultData embedBatch(array $texts, array $context = [])
 * @method static ChunkData[] chunkText(string $text, array $context = [])
 * @method static EmbeddingBatchResultData chunkAndEmbed(string $text, array $context = [])
 * @method static \Illuminate\Foundation\Bus\PendingDispatch queueBatch(string $text, array $context = [])
 *
 * @see \JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingManager
 */
final class Embedding extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JOOservices\LaravelEmbedding\Contracts\EmbeddingManager::class;
    }
}
