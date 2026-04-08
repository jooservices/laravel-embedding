<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Chunking;

use Illuminate\Support\Str;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;

/**
 * Default fixed-size chunker with configurable overlap.
 *
 * Splits text into fixed-length UTF-8 character windows.
 * Adjacent chunks share `$overlap` characters of context at their boundary.
 */
final class DefaultChunker implements Chunker
{
    /**
     * @return ChunkData[]
     */
    public function chunk(string $text, int $size, int $overlap): array
    {
        $this->validateParameters($text, $size, $overlap);

        $textLength = Str::length($text);

        // If the entire text fits in one chunk, return it immediately.
        if ($textLength <= $size) {
            return [ChunkData::make($text, 0, 0, $textLength)];
        }

        return $this->processChunks($text, $size, $overlap, $textLength);
    }

    private function validateParameters(string $text, int $size, int $overlap): void
    {
        if (trim($text) === '') {
            throw new ChunkingException('Cannot chunk an empty text string.');
        }

        if ($size <= 0) {
            throw new ChunkingException("Chunk size must be a positive integer, [{$size}] given.");
        }

        if ($overlap < 0) {
            throw new ChunkingException("Chunk overlap must be zero or positive, [{$overlap}] given.");
        }

        if ($overlap >= $size) {
            throw new ChunkingException(
                "Chunk overlap [{$overlap}] must be less than chunk size [{$size}].",
            );
        }
    }

    /**
     * @return ChunkData[]
     */
    private function processChunks(string $text, int $size, int $overlap, int $textLength): array
    {
        $chunks = [];
        $index = 0;
        $offset = 0;

        while ($offset < $textLength) {
            [$chunkContent, $chunkLength, $isLastChunk] = $this->extractChunk($text, $offset, $size, $textLength);

            $contentTrimmed = trim($chunkContent);
            if ($contentTrimmed !== '') {
                $chunks[] = ChunkData::make(
                    content: $contentTrimmed,
                    index: $index,
                    startOffset: $offset,
                    endOffset: $offset + $chunkLength,
                );
            }

            if ($isLastChunk) {
                break;
            }

            $offset = $this->resolveNextOffset($text, $offset, $chunkLength, $overlap);
            $index++;
        }

        return $chunks;
    }

    /**
     * Extract a single chunk window with word-boundary softening at the trailing edge.
     *
     * @return array{0: string, 1: int, 2: bool}
     */
    private function extractChunk(string $text, int $offset, int $size, int $textLength): array
    {
        $chunkContent = Str::substr($text, $offset, $size);
        $chunkLength = Str::length($chunkContent);
        $isLastChunk = ($offset + $chunkLength >= $textLength);

        // Rollback end cut to the nearest word boundary when not the last chunk.
        if (! $isLastChunk) {
            $lastSpacePos = mb_strrpos($chunkContent, ' ', 0, 'UTF-8');
            if ($lastSpacePos !== false && $lastSpacePos > 0) {
                $chunkContent = Str::substr($chunkContent, 0, $lastSpacePos);
                $chunkLength = Str::length($chunkContent);
            }
        }

        return [$chunkContent, $chunkLength, $isLastChunk];
    }

    /**
     * Compute the start offset of the next chunk, honouring the overlap
     * and snapping to the nearest word boundary when possible.
     */
    private function resolveNextOffset(string $text, int $offset, int $chunkLength, int $overlap): int
    {
        $nextTargetOffset = $offset + $chunkLength - $overlap;

        // Prevent infinite loop: always advance at least one character.
        if ($nextTargetOffset <= $offset) {
            $nextTargetOffset = $offset + 1;
        }

        $overlapStr = Str::substr($text, $offset, $nextTargetOffset - $offset);
        $spaceInOverlap = mb_strrpos($overlapStr, ' ', 0, 'UTF-8');

        return $spaceInOverlap !== false
            ? $offset + $spaceInOverlap + 1
            : $nextTargetOffset;
    }
}
