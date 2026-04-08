<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Models\EmbeddingBatch;
use JOOservices\LaravelEmbedding\Services\Embedding\EmbeddingBatchTracker;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EmbeddingBatchTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tracker_targets', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }

    public function test_ensure_batch_uses_target_and_source_context_and_find_returns_status(): void
    {
        $target = new class extends Model
        {
            protected $table = 'tracker_targets';

            protected $guarded = [];
        };
        $target->save();

        $tracker = new EmbeddingBatchTracker;

        $status = $tracker->ensureBatch([
            'target' => $target,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'replace_existing' => true,
            'skip_if_unchanged' => true,
            'staged_batch_token' => 'stage-1',
            'batch_id' => 'batch-1',
        ], 'markdown', '/tmp/example.md');

        $found = $tracker->find('batch-1');

        $this->assertNotNull($found);
        $this->assertSame('batch-1', $status->id);
        $this->assertSame($target->getMorphClass(), $status->targetType);
        $this->assertSame('1', $status->targetId);
        $this->assertSame('markdown', $status->sourceFormat);
        $this->assertSame('/tmp/example.md', $status->sourcePath);
        $this->assertTrue($status->replaceExisting);
        $this->assertTrue($status->skipIfUnchanged);
        $this->assertSame('stage-1', $status->stagedBatchToken);
        $this->assertSame($status->id, $found->id);
    }

    public function test_mark_chunk_lifecycle_updates_counts_and_truncates_failure_summary(): void
    {
        $batch = EmbeddingBatch::query()->create([
            'id' => 'batch-lifecycle',
            'status' => 'running',
            'total_chunks' => 2,
            'completed_chunks' => 0,
            'failed_chunks' => 0,
        ]);

        $tracker = new EmbeddingBatchTracker;

        $running = $tracker->markChunkSucceeded('batch-lifecycle');
        $failed = $tracker->markChunkFailed('batch-lifecycle', str_repeat('failure ', 200));

        $this->assertNotNull($running);
        $this->assertSame('running', $running->status);
        $this->assertNotNull($failed);
        $this->assertSame('failed', $failed->status);
        $this->assertSame(1, $failed->completedChunks);
        $this->assertSame(1, $failed->failedChunks);
        $this->assertNotNull($failed->completedAt);
        $this->assertNotNull($failed->summary);
        $this->assertLessThanOrEqual(1002, strlen($failed->summary));
        $this->assertStringEndsWith('...', $failed->summary);
        $this->assertSame(100.0, $failed->progressPercentage());
        $freshBatch = $batch->fresh();
        $this->assertNotNull($freshBatch);
        $this->assertSame('failed', $freshBatch->status);
    }

    public function test_tracker_returns_null_for_missing_batches(): void
    {
        $tracker = new EmbeddingBatchTracker;

        $this->assertNull($tracker->markDispatched('missing', 1, 'ollama', 'nomic-embed-text'));
        $this->assertNull($tracker->markChunkSucceeded('missing'));
        $this->assertNull($tracker->markChunkFailed('missing', 'boom'));
        $this->assertNull($tracker->find('missing'));
    }
}
