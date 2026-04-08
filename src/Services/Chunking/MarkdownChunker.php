<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Chunking;

use Illuminate\Support\Str;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;

/**
 * Splits markdown text intelligently.
 * Prioritizes splitting at markdown headings or paragraph boundaries (\n\n).
 * Will fall back to standard chunking if a single paragraph exceeds the chunk size.
 */
final class MarkdownChunker implements Chunker
{
    /**
     * @return ChunkData[]
     */
    public function chunk(string $text, int $size, int $overlap): array
    {
        if (trim($text) === '') {
            throw new ChunkingException('Cannot chunk an empty text string.');
        }

        $blocks = preg_split('/\n\s*\n/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($blocks === false || empty($blocks)) {
            $blocks = [$text];
        }

        $chunks = [];
        $currentBuffer = '';
        $offset = 0;
        $index = 0;
        $defaultChunker = new DefaultChunker;

        foreach ($blocks as $block) {
            if (Str::length($currentBuffer) + Str::length($block) > $size && $currentBuffer !== '') {
                $this->processBuffer($currentBuffer, $offset, $index, $chunks);
            }

            if (Str::length($block) > $size) {
                if (trim($currentBuffer) !== '') {
                    $this->processBuffer($currentBuffer, $offset, $index, $chunks);
                }
                $this->processOversizedBlock($block, $size, $overlap, $offset, $index, $chunks, $defaultChunker);
            } else {
                $currentBuffer .= ($currentBuffer === '' ? '' : "\n\n").trim($block);
            }
        }

        if (trim($currentBuffer) !== '') {
            $this->processBuffer($currentBuffer, $offset, $index, $chunks);
        }

        return $chunks;
    }

    /**
     * Flush the accumulated buffer as a single chunk.
     *
     * @param  ChunkData[]  $chunks
     */
    private function processBuffer(string &$currentBuffer, int &$offset, int &$index, array &$chunks): void
    {
        $content = trim($currentBuffer);
        if ($content !== '') {
            $contentLength = Str::length($content);
            $chunks[] = ChunkData::make(
                content: $content,
                index: $index++,
                startOffset: $offset,
                endOffset: $offset + $contentLength,
            );
            $offset += $contentLength;
        }
        $currentBuffer = '';
    }

    /**
     * Delegate a block that exceeds $size to DefaultChunker and append its sub-chunks.
     *
     * @param  ChunkData[]  $chunks
     */
    private function processOversizedBlock(
        string $block,
        int $size,
        int $overlap,
        int &$offset,
        int &$index,
        array &$chunks,
        DefaultChunker $defaultChunker,
    ): void {
        $subChunks = $defaultChunker->chunk($block, $size, $overlap);
        foreach ($subChunks as $subChunk) {
            $subLength = Str::length($subChunk->content);
            $chunks[] = ChunkData::make(
                content: $subChunk->content,
                index: $index++,
                startOffset: $offset,
                endOffset: $offset + $subLength,
            );
            $offset += $subLength;
        }
    }
}
