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
}
