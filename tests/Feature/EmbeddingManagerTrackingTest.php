<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use JOOservices\LaravelEmbedding\Contracts\Chunker;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingProvider;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingBatchResultData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingManager;
use JOOservices\LaravelEmbedding\Services\Ingestion\ContentNormalizer;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use Mockery;

final class EmbeddingManagerTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_queue_tracked_persists_batch_status_and_dispatches_job(): void
    {
        Queue::fake();

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $provider->shouldReceive('providerName')->once()->andReturn('ollama');
        $provider->shouldReceive('modelName')->once()->andReturn('nomic-embed-text');

        $manager = new EmbeddingManager(
            $chunker,
            $provider,
            null,
            new ContentNormalizer,
            new EmbeddingBatchTracker,
            false,
            100,
            10,
        );

        $status = $manager->queueTracked('queued text', [
            'target_type' => 'document',
            'target_id' => 'doc-1',
            'namespace' => 'kb',
        ]);

        $found = $manager->batchStatus($status->id);

        $this->assertNotNull($found);
        $this->assertSame('document', $status->targetType);
        $this->assertSame('doc-1', $status->targetId);
        $this->assertSame('kb', $status->namespace);
        Queue::assertPushed(ProcessEmbeddingBatchJob::class, static function (ProcessEmbeddingBatchJob $job) use ($status): bool {
            return ($job->context['batch_id'] ?? null) === $status->id;
        });
    }

    public function test_queue_helpers_assign_expected_source_formats(): void
    {
        Queue::fake();

        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);
        $provider->shouldReceive('providerName')->times(3)->andReturn('ollama');
        $provider->shouldReceive('modelName')->times(3)->andReturn('nomic-embed-text');

        $manager = new EmbeddingManager(
            $chunker,
            $provider,
            null,
            new ContentNormalizer,
            new EmbeddingBatchTracker,
            false,
            100,
            10,
        );

        $html = $manager->queueHtml('<h1>Hello</h1><p>World</p>');
        $markdown = $manager->queueMarkdown("# Heading\n\nBody");

        $path = tempnam(sys_get_temp_dir(), 'embedding-file');
        if ($path === false) {
            $this->fail('Unable to create temporary file for queueFile test.');
        }

        $realPath = $path.'.txt';
        rename($path, $realPath);
        file_put_contents($realPath, " file \n content ");

        try {
            $file = $manager->queueFile($realPath);
        } finally {
            @unlink($realPath);
        }

        $this->assertSame('html', $html->sourceFormat);
        $this->assertSame('markdown', $markdown->sourceFormat);
        $this->assertSame('text', $file->sourceFormat);
        $this->assertSame($realPath, $file->sourcePath);
        Queue::assertPushed(ProcessEmbeddingBatchJob::class, 3);
    }

    public function test_ingest_helpers_normalize_source_before_embedding(): void
    {
        $chunker = Mockery::mock(Chunker::class);
        $provider = Mockery::mock(EmbeddingProvider::class);

        $htmlChunk = ChunkData::make('Hello world', 0, 0, 11);
        $markdownChunk = ChunkData::make("Title\n\nBody", 0, 0, 11);
        $textChunk = ChunkData::make("plain\n text", 0, 0, 11);

        $chunker->shouldReceive('chunk')->once()->with('Hello world', 100, 10)->andReturn([$htmlChunk]);
        $chunker->shouldReceive('chunk')->once()->with("Title\n\nBody", 100, 10)->andReturn([$markdownChunk]);
        $chunker->shouldReceive('chunk')->once()->with("plain\n text", 100, 10)->andReturn([$textChunk]);

        $provider->shouldReceive('embedBatch')
            ->once()
            ->with([$htmlChunk])
            ->andReturn(new EmbeddingBatchResultData([
                EmbeddingVectorData::make($htmlChunk, [0.1, 0.2], 'ollama', 'nomic-embed-text'),
            ], 'ollama', 'nomic-embed-text'));
        $provider->shouldReceive('embedBatch')
            ->once()
            ->with([$markdownChunk])
            ->andReturn(new EmbeddingBatchResultData([
                EmbeddingVectorData::make($markdownChunk, [0.1, 0.2], 'ollama', 'nomic-embed-text'),
            ], 'ollama', 'nomic-embed-text'));
        $provider->shouldReceive('embedBatch')
            ->once()
            ->with([$textChunk])
            ->andReturn(new EmbeddingBatchResultData([
                EmbeddingVectorData::make($textChunk, [0.1, 0.2], 'ollama', 'nomic-embed-text'),
            ], 'ollama', 'nomic-embed-text'));

        $manager = new EmbeddingManager(
            $chunker,
            $provider,
            null,
            new ContentNormalizer,
            new EmbeddingBatchTracker,
            false,
            100,
            10,
        );

        $path = tempnam(sys_get_temp_dir(), 'embedding-ingest');
        if ($path === false) {
            $this->fail('Unable to create temporary file for ingestFile test.');
        }

        $realPath = $path.'.txt';
        rename($path, $realPath);
        file_put_contents($realPath, " plain \n text ");

        try {
            $html = $manager->ingestHtml('<p>Hello <strong>world</strong></p>');
            $markdown = $manager->ingestMarkdown("# Title\n\nBody");
            $file = $manager->ingestFile($realPath);
        } finally {
            @unlink($realPath);
        }

        $this->assertSame(1, $html->count());
        $this->assertSame(1, $markdown->count());
        $this->assertSame(1, $file->count());
    }
}
