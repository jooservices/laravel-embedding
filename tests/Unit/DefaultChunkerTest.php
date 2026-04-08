<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;
use JOOservices\LaravelEmbedding\Services\Chunking\DefaultChunker;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class DefaultChunkerTest extends TestCase
{
    private DefaultChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new DefaultChunker;
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_short_text_returns_single_chunk(): void
    {
        $text = 'Hello world';
        $chunks = $this->chunker->chunk($text, 100, 0);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]->content);
        $this->assertSame(0, $chunks[0]->index);
        $this->assertSame(0, $chunks[0]->startOffset);
        $this->assertSame(11, $chunks[0]->endOffset);
    }

    public function test_text_exactly_chunk_size_returns_single_chunk(): void
    {
        $text = str_repeat('a', 50);
        $chunks = $this->chunker->chunk($text, 50, 0);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]->content);
    }

    public function test_text_splits_into_multiple_chunks(): void
    {
        $text = str_repeat('a', 250);
        $chunks = $this->chunker->chunk($text, 100, 0);

        // 0-99 (100), 100-199 (100), 200-249 (50) = 3 chunks
        $this->assertCount(3, $chunks);
    }

    public function test_chunk_indices_are_sequential(): void
    {
        $text = str_repeat('x', 300);
        $chunks = $this->chunker->chunk($text, 100, 0);

        foreach ($chunks as $i => $chunk) {
            $this->assertSame($i, $chunk->index);
        }
    }

    public function test_chunks_with_overlap_share_boundary_content(): void
    {
        // 'ABCDE...' with size=5, overlap=2 → [0,5), [3,8), ...
        $text = 'ABCDEFGHIJ'; // 10 chars
        $chunks = $this->chunker->chunk($text, 5, 2);

        // First chunk = ABCDE, second = DEFGH (overlap DE), third = GHIJ
        $this->assertStringEndsWith(
            substr($chunks[0]->content, -2), // last 2 of first
            substr($chunks[1]->content, 0, 2), // first 2 of second
        );
    }

    public function test_content_hash_is_sha256(): void
    {
        $text = 'Hash me!';
        $chunks = $this->chunker->chunk($text, 100, 0);

        $this->assertSame(hash('sha256', $text), $chunks[0]->contentHash);
    }

    public function test_multibyte_text_is_split_correctly(): void
    {
        // 10 two-byte Japanese characters = 10 mb_strlen units
        $text = str_repeat('あ', 10);
        $chunks = $this->chunker->chunk($text, 5, 0);

        $this->assertCount(2, $chunks);
        $this->assertSame(str_repeat('あ', 5), $chunks[0]->content);
        $this->assertSame(str_repeat('あ', 5), $chunks[1]->content);
    }

    // -------------------------------------------------------------------------
    // Failure cases
    // -------------------------------------------------------------------------

    public function test_empty_text_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('   ', 100, 0);
    }

    public function test_zero_chunk_size_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('Some text', 0, 0);
    }

    public function test_negative_chunk_size_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('Some text', -1, 0);
    }

    public function test_negative_overlap_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('Some text', 100, -1);
    }

    public function test_overlap_equal_to_size_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('Some text', 50, 50);
    }

    public function test_overlap_greater_than_size_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('Some text', 50, 100);
    }
}
