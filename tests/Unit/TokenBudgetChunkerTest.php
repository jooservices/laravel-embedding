<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

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
}
