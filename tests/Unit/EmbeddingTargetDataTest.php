<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EmbeddingTargetDataTest extends TestCase
{
    public function test_from_model_uses_morph_class_and_key(): void
    {
        $model = new class extends Model
        {
            protected $table = 'documents';

            public function getMorphClass(): string
            {
                return 'docs';
            }

            public function getKey(): int
            {
                return 42;
            }
        };

        $target = EmbeddingTargetData::fromModel($model, 'knowledge');

        $this->assertSame('docs', $target->type);
        $this->assertSame(42, $target->id);
        $this->assertSame('knowledge', $target->namespace);
    }

    public function test_from_context_prefers_namespace_from_context_for_embedded_target_data(): void
    {
        $target = EmbeddingTargetData::fromContext([
            'target' => new EmbeddingTargetData('docs', 'abc', null),
            'namespace' => 'runtime',
        ]);

        $this->assertNotNull($target);
        $this->assertSame('docs', $target->type);
        $this->assertSame('abc', $target->id);
        $this->assertSame('runtime', $target->namespace);
    }

    public function test_from_context_builds_target_from_model(): void
    {
        $model = new class extends Model
        {
            protected $table = 'documents';

            public function getMorphClass(): string
            {
                return 'docs';
            }

            public function getKey(): string
            {
                return 'doc-1';
            }
        };

        $target = EmbeddingTargetData::fromContext([
            'target' => $model,
            'namespace' => 'knowledge',
        ]);

        $this->assertNotNull($target);
        $this->assertSame('docs', $target->type);
        $this->assertSame('doc-1', $target->id);
        $this->assertSame('knowledge', $target->namespace);
    }

    public function test_from_context_returns_null_for_blank_target_type(): void
    {
        $target = EmbeddingTargetData::fromContext([
            'target_type' => '   ',
            'target_id' => 'doc-1',
        ]);

        $this->assertNull($target);
    }

    public function test_from_context_returns_null_for_non_scalar_target_id(): void
    {
        $target = EmbeddingTargetData::fromContext([
            'target_type' => 'docs',
            'target_id' => ['doc-1'],
        ]);

        $this->assertNull($target);
    }

    public function test_from_context_casts_scalar_values_to_strings(): void
    {
        $target = EmbeddingTargetData::fromContext([
            'target_type' => ' docs ',
            'target_id' => 123,
            'namespace' => 99,
        ]);

        $this->assertNotNull($target);
        $this->assertSame('docs', $target->type);
        $this->assertSame('123', $target->id);
        $this->assertSame('99', $target->namespace);
    }
}
