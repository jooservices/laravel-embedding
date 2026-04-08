<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Exceptions\ChunkingException;
use JOOservices\LaravelEmbedding\Services\Chunking\TokenBudgetChunker;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class TokenBudgetChunkerTest extends TestCase
{
    public function test_token_budget_chunker_respects_token_window(): void
    {
        $chunker = new TokenBudgetChunker;

        $chunks = $chunker->chunk('one two three four five', 2, 0);

        $this->assertCount(3, $chunks);
        $this->assertSame('one two', $chunks[0]->content);
        $this->assertSame('three four', $chunks[1]->content);
        $this->assertSame('five', $chunks[2]->content);
    }

    public function test_token_budget_chunker_throws_on_empty_text(): void
    {
        $this->expectException(ChunkingException::class);
        $this->expectExceptionMessage('Cannot chunk an empty text string.');

        (new TokenBudgetChunker)->chunk('   ', 2, 0);
    }

    public function test_token_budget_chunker_throws_on_non_positive_size(): void
    {
        $this->expectException(ChunkingException::class);
        $this->expectExceptionMessage('Chunk size must be greater than zero.');

        (new TokenBudgetChunker)->chunk('one two', 0, 0);
    }

    public function test_token_budget_chunker_clamps_overlap_to_safe_step_size(): void
    {
        $chunks = (new TokenBudgetChunker)->chunk('one two three four', 2, 10);

        $this->assertCount(4, $chunks);
        $this->assertSame('one two', $chunks[0]->content);
        $this->assertSame('two three', $chunks[1]->content);
    }
}
