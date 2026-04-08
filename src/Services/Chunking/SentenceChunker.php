<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Chunking;

use Illuminate\Support\Str;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;

final class SentenceChunker implements Chunker
{
    /**
     * @return ChunkData[]
     */
    public function chunk(string $text, int $size, int $overlap): array
    {
        if (trim($text) === '') {
            throw new ChunkingException('Cannot chunk an empty text string.');
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if ($sentences === false || $sentences === []) {
            return (new DefaultChunker)->chunk($text, $size, $overlap);
        }

        $chunks = [];
        $buffer = '';
        $offset = 0;
        $index = 0;
        $fallback = new DefaultChunker;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            $candidate = $buffer === '' ? $sentence : $buffer.' '.$sentence;
            if (Str::length($candidate) <= $size) {
                $buffer = $candidate;

                continue;
            }

            if ($buffer !== '') {
                $this->flush($buffer, $offset, $index, $chunks);
            }

            if (Str::length($sentence) > $size) {
                foreach ($fallback->chunk($sentence, $size, $overlap) as $subChunk) {
                    $subLength = Str::length($subChunk->content);
                    $chunks[] = ChunkData::make(
                        content: $subChunk->content,
                        index: $index++,
                        startOffset: $offset,
                        endOffset: $offset + $subLength,
                    );
                    $offset += $subLength;
                }

                continue;
            }

            $buffer = $sentence;
        }

        if ($buffer !== '') {
            $this->flush($buffer, $offset, $index, $chunks);
        }

        return $chunks;
    }

    /**
     * @param  ChunkData[]  $chunks
     */
    private function flush(string &$buffer, int &$offset, int &$index, array &$chunks): void
    {
        $content = trim($buffer);
        if ($content === '') {
            $buffer = '';

            return;
        }

        $length = Str::length($content);
        $chunks[] = ChunkData::make(
            content: $content,
            index: $index++,
            startOffset: $offset,
            endOffset: $offset + $length,
        );
        $offset += $length;
        $buffer = '';
    }
}
