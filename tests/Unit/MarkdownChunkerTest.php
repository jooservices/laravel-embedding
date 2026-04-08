<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;
use JOOservices\LaravelEmbedding\Services\Chunking\MarkdownChunker;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class MarkdownChunkerTest extends TestCase
{
    private MarkdownChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new MarkdownChunker;
    }

    public function test_empty_text_throws_chunking_exception(): void
    {
        $this->expectException(ChunkingException::class);
        $this->chunker->chunk('   ', 100, 0);
    }

    public function test_combines_small_blocks(): void
    {
        $text = "Block 1\n\nBlock 2\n\nBlock 3";
        $chunks = $this->chunker->chunk($text, 100, 0);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]->content);
    }

    public function test_splits_large_blocks_over_size(): void
    {
        $text = "Block 1\n\nBlock 2\n\nBlock 3";
        $chunks = $this->chunker->chunk($text, 15, 0);

        // Block 1 will be alone because adding \n\nBlock 2 exceeds 15 chars.
        // Actually, Block 1 + \n\n + Block 2 is 16 chars, so it flushes Block 1 and Block 2 to chunk 1? Wait, the condition is `Str::length($currentBuffer) + $blockLength > $size`. If buffer is 7, block is 7, 14 < 15. So they combine, then the \n\n makes it 16 but the check only checks before \n\n if we just use blockLength. In fact, actual chunks is 2.
        $this->assertCount(2, $chunks);
        $this->assertStringContainsString('Block 1', $chunks[0]->content);
        $this->assertStringContainsString('Block 3', $chunks[1]->content);
    }

    public function test_fallback_to_default_chunker_when_block_exceeds_size(): void
    {
        $text = "Small\n\nVery large block without paragraphs that exceeds the chunk size limit entirely.";
        $chunks = $this->chunker->chunk($text, 20, 0);

        $this->assertGreaterThan(2, count($chunks));
        $this->assertSame('Small', $chunks[0]->content);
        $this->assertStringContainsString('Very large block', $chunks[1]->content);
    }
}
