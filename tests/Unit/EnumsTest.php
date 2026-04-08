<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\Enums\ChunkingStrategy;
use JOOservices\LaravelEmbedding\Enums\EmbeddingModel;
use JOOservices\LaravelEmbedding\Enums\EmbeddingProvider;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EnumsTest extends TestCase
{
    public function test_chunking_strategy(): void
    {
        $this->assertSame('default', ChunkingStrategy::Default->value);
        $this->assertSame('sentence', ChunkingStrategy::Sentence->value);
        $this->assertSame('markdown', ChunkingStrategy::Markdown->value);
        $this->assertSame('token', ChunkingStrategy::Token->value);
        $this->assertSame(ChunkingStrategy::Default, ChunkingStrategy::fromConfig('invalid'));
        $this->assertSame(ChunkingStrategy::Sentence, ChunkingStrategy::fromConfig('sentence'));
        $this->assertSame(ChunkingStrategy::Markdown, ChunkingStrategy::fromConfig('markdown'));
        $this->assertSame(ChunkingStrategy::Token, ChunkingStrategy::fromConfig('token'));
    }

    public function test_embedding_model(): void
    {
        $this->assertSame(1536, EmbeddingModel::TextEmbedding3Small->dimension());
        $this->assertSame(3072, EmbeddingModel::TextEmbedding3Large->dimension());
        $this->assertSame(1536, EmbeddingModel::TextEmbeddingAda002->dimension());
        $this->assertSame(768, EmbeddingModel::NomicEmbedText->dimension());
        $this->assertSame(384, EmbeddingModel::AllMiniLM->dimension());
        $this->assertSame(1024, EmbeddingModel::MxbaiEmbedLarge->dimension());
    }

    public function test_embedding_provider(): void
    {
        $this->assertSame('openai', EmbeddingProvider::OpenAI->value);
        $this->assertSame('ollama', EmbeddingProvider::Ollama->value);
        $this->assertSame('OpenAI', EmbeddingProvider::OpenAI->label());
        $this->assertSame('Ollama', EmbeddingProvider::Ollama->label());
        $this->assertSame(EmbeddingProvider::Ollama, EmbeddingProvider::fromConfig('ollama'));
    }

    public function test_embedding_provider_throws_on_invalid(): void
    {
        $this->expectException(\JOOservices\LaravelEmbedding\Exceptions\UnsupportedEmbeddingProviderException::class);
        EmbeddingProvider::fromConfig('invalid');
    }
}
