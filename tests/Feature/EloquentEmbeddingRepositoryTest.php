<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;
use JOOservices\LaravelEmbedding\DTOs\StoredEmbeddingData;
use JOOservices\LaravelEmbedding\Repositories\EloquentEmbeddingRepository;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EloquentEmbeddingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentEmbeddingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('repository_targets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        $this->repository = new EloquentEmbeddingRepository;
    }

    private function makeTarget(): Model
    {
        $target = new class extends Model
        {
            protected $table = 'repository_targets';

            protected $guarded = [];
        };
        $target->save();

        return $target;
    }

    private function makeVector(int $index = 0): EmbeddingVectorData
    {
        $chunk = ChunkData::make(
            content: "Test chunk content number {$index}",
            index: $index,
            startOffset: $index * 30,
            endOffset: ($index + 1) * 30,
        );

        return EmbeddingVectorData::make(
            chunk: $chunk,
            vector: [0.1, 0.2, 0.3],
            provider: 'ollama',
            model: 'nomic-embed-text',
        );
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_persists_embedding_and_returns_stored_data(): void
    {
        $vector = $this->makeVector();
        $result = $this->repository->store($vector);

        $this->assertInstanceOf(StoredEmbeddingData::class, $result);
        $this->assertNotNull($result->id);
        $this->assertSame('ollama', $result->vector->provider);
        $this->assertSame($vector->chunk->contentHash, $result->vector->chunk->contentHash);
        $this->assertNull($result->targetType);
        $this->assertNull($result->targetId);
        $this->assertNull($result->embeddableType);
        $this->assertNull($result->embeddableId);
    }

    public function test_store_persists_meta_alongside_embedding(): void
    {
        $vector = $this->makeVector();
        $result = $this->repository->store($vector, null, ['source' => 'test-doc', 'lang' => 'en']);

        $this->assertSame('test-doc', $result->meta['source']);
        $this->assertSame('en', $result->meta['lang']);
    }

    // -------------------------------------------------------------------------
    // storeBatch
    // -------------------------------------------------------------------------

    public function test_store_batch_persists_multiple_embeddings(): void
    {
        $vectors = [$this->makeVector(0), $this->makeVector(1), $this->makeVector(2)];
        $results = $this->repository->storeBatch($vectors);

        $this->assertCount(3, $results);
        foreach ($results as $i => $result) {
            $this->assertSame($i, $result->vector->chunk->index);
        }
    }

    public function test_store_updates_existing_identity_instead_of_creating_duplicate(): void
    {
        $first = $this->makeVector(0);
        $second = EmbeddingVectorData::make(
            chunk: $first->chunk,
            vector: [9.0, 8.0, 7.0],
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $this->repository->store($first);
        $this->repository->store($second);

        $this->assertDatabaseCount('embeddings', 1);
    }

    // -------------------------------------------------------------------------
    // findByHash
    // -------------------------------------------------------------------------

    public function test_find_by_hash_returns_stored_data_for_known_hash(): void
    {
        $vector = $this->makeVector();
        $this->repository->store($vector);

        $found = $this->repository->findByHash($vector->chunk->contentHash);

        $this->assertNotNull($found);
        $this->assertSame($vector->chunk->contentHash, $found->vector->chunk->contentHash);
    }

    public function test_find_by_hash_returns_null_for_unknown_hash(): void
    {
        $result = $this->repository->findByHash('nonexistent_hash_value_12345');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // deleteForTarget
    // -------------------------------------------------------------------------

    public function test_delete_for_target_removes_associated_records(): void
    {
        $target = $this->makeTarget();
        $this->repository->store($this->makeVector(0), $target);
        $this->repository->store($this->makeVector(1));

        $count = $this->repository->deleteForTarget($target);

        $this->assertSame(1, $count);
        $this->assertDatabaseCount('embeddings', 1);
    }

    public function test_store_supports_non_eloquent_target_references(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-123', 'kb');

        $stored = $this->repository->store($this->makeVector(0), $target, ['source' => 'external']);

        $this->assertSame('document', $stored->targetType);
        $this->assertSame('doc-123', $stored->targetId);
        $this->assertSame('kb', $stored->namespace);
    }

    public function test_replace_for_target_replaces_existing_embedding_set(): void
    {
        $target = $this->makeTarget();

        $this->repository->store($this->makeVector(0), $target);
        $this->repository->store($this->makeVector(1), $target);

        $replacement = [$this->makeVector(2)];
        $this->repository->replaceForTarget($replacement, $target, ['source' => 'replacement']);

        $this->assertDatabaseCount('embeddings', 1);
        $stored = $this->repository->findByHash($replacement[0]->chunk->contentHash);
        $this->assertNotNull($stored);
        $this->assertSame('replacement', $stored->meta['source']);
    }

    public function test_find_for_target_returns_ordered_results_with_filters(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-456', 'kb');
        $this->repository->store($this->makeVector(1), $target, ['lang' => 'en']);
        $this->repository->store($this->makeVector(0), $target, ['lang' => 'en']);
        $this->repository->store($this->makeVector(0), new EmbeddingTargetData('document', 'doc-789', 'kb'));

        $results = $this->repository->findForTarget($target, [
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'namespace' => 'kb',
        ]);

        $this->assertCount(2, $results);
        $this->assertSame([0, 1], $results->map(fn (StoredEmbeddingData $result): int => $result->vector->chunk->index)->all());
    }

    public function test_has_matching_content_hashes_returns_true_for_identical_target_set(): void
    {
        $target = new EmbeddingTargetData('document', 'doc-999', 'kb');
        $vectors = [$this->makeVector(0), $this->makeVector(1)];
        $this->repository->storeBatch($vectors, $target);

        $result = $this->repository->hasMatchingContentHashes(
            $target,
            array_map(static fn (EmbeddingVectorData $vector): string => $vector->chunk->contentHash, $vectors),
            'ollama',
            'nomic-embed-text',
        );

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Contract binding
    // -------------------------------------------------------------------------

    public function test_repository_contract_is_bound_when_persistence_enabled(): void
    {
        $repo = $this->app->make(EmbeddingRepository::class);

        $this->assertInstanceOf(EloquentEmbeddingRepository::class, $repo);
    }
}
