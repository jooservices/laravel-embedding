<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Enums;

/**
 * Known embedding model identifiers for supported providers.
 *
 * This enum serves as a reference for well-known model strings.
 * Providers may accept additional model names not listed here;
 * the config value is used verbatim when constructing requests.
 */
enum EmbeddingModel: string
{
    // Ollama models
    case NomicEmbedText = 'nomic-embed-text';
    case MxbaiEmbedLarge = 'mxbai-embed-large';
    case AllMiniLM = 'all-minilm';

    // OpenAI models (placeholders for future implementation)
    case TextEmbedding3Small = 'text-embedding-3-small';
    case TextEmbedding3Large = 'text-embedding-3-large';
    case TextEmbeddingAda002 = 'text-embedding-ada-002';

    /**
     * Return the expected output dimension for models where it is fixed.
     * Returns null for models where dimension is dynamic or unknown.
     */
    public function dimension(): ?int
    {
        return match ($this) {
            self::NomicEmbedText => 768,
            self::MxbaiEmbedLarge => 1024,
            self::AllMiniLM => 384,
            self::TextEmbedding3Small => 1536,
            self::TextEmbedding3Large => 3072,
            self::TextEmbeddingAda002 => 1536,
        };
    }
}
