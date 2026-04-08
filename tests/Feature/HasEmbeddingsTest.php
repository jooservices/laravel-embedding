<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Jobs\ProcessEmbeddingBatchJob;
use JOOservices\LaravelEmbedding\Tests\TestCase;
use JOOservices\LaravelEmbedding\Traits\HasEmbeddings;
use RuntimeException;

class EmbeddableModel extends Model
{
    use HasEmbeddings;

    protected $table = 'embeddable_models';

    protected $guarded = [];

    public function getEmbeddableContent(): string
    {
        return $this->attributes['content'] ?? '';
    }
}

class BadEmbeddableModel extends Model
{
    use HasEmbeddings;

    protected $table = 'embeddable_models';

    protected $guarded = [];
}

final class HasEmbeddingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        Schema::create('embeddable_models', function (Blueprint $table) {
            $table->id();
            $table->string('content')->nullable();
            $table->timestamps();
        });
    }

    public function test_embeddings_relationship(): void
    {
        $model = EmbeddableModel::create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $model->embeddings());
    }

    public function test_deleting_model_wipes_embeddings(): void
    {
        $model = EmbeddableModel::create();
        $model->embeddings()->create([
            'embedding' => '[1.0, 2.0]',
            'content' => 'hello',
            'content_hash' => 'hash',
            'dimension' => 2,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'chunk_index' => 0,
        ]);

        $this->assertEquals(1, $model->embeddings()->count());
        $model->delete();
        $this->assertEquals(0, \JOOservices\LaravelEmbedding\Models\Embedding::count());
    }

    public function test_queue_embedding_throws_without_method(): void
    {
        $this->expectException(RuntimeException::class);
        $model = BadEmbeddableModel::create();
        $model->queueEmbedding();
    }

    public function test_queue_embedding_ignores_empty_content(): void
    {
        Queue::fake();

        $model = EmbeddableModel::create(['content' => '']);
        $model->queueEmbedding();

        // No job should be dispatched for empty content
        Queue::assertNothingPushed();
    }

    public function test_queue_embedding_dispatches_batch_job(): void
    {
        Queue::fake();

        $model = EmbeddableModel::create(['content' => 'hello world']);
        $model->queueEmbedding(['extra' => 'meta']);

        Queue::assertPushed(ProcessEmbeddingBatchJob::class, function (ProcessEmbeddingBatchJob $job) {
            return $job->text === 'hello world';
        });
    }
}
