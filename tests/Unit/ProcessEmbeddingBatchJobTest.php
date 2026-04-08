<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;

final class ProcessEmbeddingBatchJobTest extends TestCase
{
    public function test_handle_calls_chunk_and_embed(): void
    {
        $manager = Mockery::mock(EmbeddingManager::class);
        $manager->shouldReceive('chunkAndEmbed')
            ->once()
            ->with('some text', ['foo' => 'bar'])
            ->andReturn(new EmbeddingBatchResultData([], 'openai', 'model-name'));

        $job = new ProcessEmbeddingBatchJob('some text', ['foo' => 'bar']);
        $job->handle($manager);

        $this->assertTrue(true);
    }

    public function test_middleware_returns_without_overlapping_when_concurrency_key_is_present(): void
    {
        $job = new ProcessEmbeddingBatchJob('some text', ['foo' => 'bar'], 'docs:1');

        $this->assertCount(1, $job->middleware());
    }
}
