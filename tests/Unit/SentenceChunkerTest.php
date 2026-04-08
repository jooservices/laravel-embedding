<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Services\Chunking\SentenceChunker;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class SentenceChunkerTest extends TestCase
{
    public function test_sentence_chunker_prefers_sentence_boundaries(): void
    {
        $chunker = new SentenceChunker;

        $chunks = $chunker->chunk('First sentence. Second sentence. Third sentence.', 30, 0);

        $this->assertCount(3, $chunks);
        $this->assertSame('First sentence.', $chunks[0]->content);
    }

    public function test_sentence_chunker_ignores_blank_sentences_and_flushes_last_buffer(): void
    {
        $chunker = new SentenceChunker;

        $chunks = $chunker->chunk(" First sentence.   \n\n Second sentence?  ", 20, 0);

        $this->assertCount(2, $chunks);
        $this->assertSame('First sentence.', $chunks[0]->content);
        $this->assertSame('Second sentence?', $chunks[1]->content);
    }

    public function test_sentence_chunker_falls_back_when_single_sentence_exceeds_chunk_size(): void
    {
        $chunker = new SentenceChunker;

        $chunks = $chunker->chunk('abcdefghijklmnopqrstuvwxyz.', 10, 0);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame(0, $chunks[0]->index);
        $this->assertSame(1, $chunks[1]->index);
    }

    public function test_sentence_chunker_returns_single_chunk_when_content_fits_after_trimming(): void
    {
        $chunker = new SentenceChunker;

        $chunks = $chunker->chunk('   Compact sentence.   ', 50, 0);

        $this->assertCount(1, $chunks);
        $this->assertSame('Compact sentence.', $chunks[0]->content);
    }
}
