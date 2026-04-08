<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Embedding\Concerns;

use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchStatusData;
use JOOservices\LaravelEmbedding\Services\Ingestion\ContentNormalizer;

trait HandlesEmbeddingIngestion
{
    public function ingestHtml(string $html, array $context = []): EmbeddingBatchResultData
    {
        return $this->chunkAndEmbed(
            $this->ingestionNormalizer()->normalizeHtml($html),
            [...$context, 'source_format' => 'html'],
        );
    }

    public function ingestMarkdown(string $markdown, array $context = []): EmbeddingBatchResultData
    {
        return $this->chunkAndEmbed(
            $this->ingestionNormalizer()->normalizeMarkdown($markdown),
            [...$context, 'source_format' => 'markdown'],
        );
    }

    public function ingestFile(string $path, array $context = []): EmbeddingBatchResultData
    {
        $normalized = $this->ingestionNormalizer()->normalizeFile($path);

        return $this->chunkAndEmbed($normalized['content'], [
            ...$context,
            'source_format' => $normalized['format'],
            'source_path' => $normalized['path'],
        ]);
    }

    public function queueHtml(string $html, array $context = []): EmbeddingBatchStatusData
    {
        return $this->queueTracked(
            $this->ingestionNormalizer()->normalizeHtml($html),
            [...$context, 'source_format' => 'html'],
        );
    }

    public function queueMarkdown(string $markdown, array $context = []): EmbeddingBatchStatusData
    {
        return $this->queueTracked(
            $this->ingestionNormalizer()->normalizeMarkdown($markdown),
            [...$context, 'source_format' => 'markdown'],
        );
    }

    public function queueFile(string $path, array $context = []): EmbeddingBatchStatusData
    {
        $normalized = $this->ingestionNormalizer()->normalizeFile($path);

        return $this->queueTracked($normalized['content'], [
            ...$context,
            'source_format' => $normalized['format'],
            'source_path' => $normalized['path'],
        ]);
    }

    private function ingestionNormalizer(): ContentNormalizer
    {
        return $this->normalizer ?? new ContentNormalizer;
    }
}
