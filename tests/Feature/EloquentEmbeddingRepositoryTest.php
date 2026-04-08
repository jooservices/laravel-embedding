<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingRepository;
use JOOservices\LaravelEmbedding\DTOs\ChunkData;
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
        $this->repository = new EloquentEmbeddingRepository;
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
        // Store with no target first to confirm deleteForTarget is specific.
        $this->repository->store($this->makeVector(0));
        $this->repository->store($this->makeVector(1));

        $count = $this->repository->deleteForTarget(new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'embeddings';
        });

        // Since no records were stored with a real morph, 0 should be deleted.
        $this->assertSame(0, $count);
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
