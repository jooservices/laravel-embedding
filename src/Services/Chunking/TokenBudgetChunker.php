<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Chunking;

use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;

final class TokenBudgetChunker implements Chunker
{
    /**
     * @return ChunkData[]
     */
    public function chunk(string $text, int $size, int $overlap): array
    {
        if (trim($text) === '') {
            throw new ChunkingException('Cannot chunk an empty text string.');
        }

        if ($size <= 0) {
            throw new ChunkingException('Chunk size must be greater than zero.');
        }

        $tokens = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === false || $tokens === []) {
            throw new ChunkingException('Unable to split text into tokens.');
        }

        $chunks = [];
        $index = 0;
        $cursor = 0;
        $count = count($tokens);
        $overlap = max(0, min($overlap, $size - 1));
        $step = max(1, $size - $overlap);

        while ($cursor < $count) {
            $slice = array_slice($tokens, $cursor, $size);
            $content = implode(' ', $slice);
            $startOffset = mb_strpos($text, $content);
            if ($startOffset === false) {
                $startOffset = 0;
            }

            $chunks[] = ChunkData::make(
                content: $content,
                index: $index++,
                startOffset: $startOffset,
                endOffset: $startOffset + mb_strlen($content),
            );

            $cursor += $step;
        }

        return $chunks;
    }
}
