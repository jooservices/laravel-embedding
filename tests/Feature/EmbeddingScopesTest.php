<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JOOservices\LaravelEmbedding\Models\Embedding;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EmbeddingScopesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function test_metadata_and_target_scopes_filter_embeddings(): void
    {
        Embedding::create([
            'target_type' => 'document',
            'target_id' => 'doc-1',
            'namespace' => 'knowledge',
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimension' => 2,
            'chunk_index' => 0,
            'content' => 'alpha',
            'content_hash' => 'hash-1',
            'embedding' => [0.1, 0.2],
            'meta' => ['lang' => 'en'],
        ]);

        Embedding::create([
            'target_type' => 'document',
            'target_id' => 'doc-2',
            'namespace' => 'knowledge',
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimension' => 2,
            'chunk_index' => 0,
            'content' => 'beta',
            'content_hash' => 'hash-2',
            'embedding' => [0.1, 0.2],
            'meta' => ['lang' => 'fr'],
        ]);

        $results = Embedding::query()
            ->forTarget('document', 'doc-1')
            ->forProvider('ollama')
            ->forModel('nomic-embed-text')
            ->inNamespace('knowledge')
            ->withMetaFilter('lang', 'en')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame('doc-1', $results->first()?->target_id);
    }
}
