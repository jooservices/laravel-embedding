<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Enums;

enum EmbeddingProvider: string
{
    case Ollama = 'ollama';
    case OpenAI = 'openai';

    /**
     * Return the human-readable label for the provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ollama => 'Ollama',
            self::OpenAI => 'OpenAI',
        };
    }

    /**
     * Instantiate from the config string value safely.
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\UnsupportedEmbeddingProviderException
     */
    public static function fromConfig(string $value): self
    {
        $case = self::tryFrom($value);

        if ($case === null) {
            throw new \JOOservices\LaravelEmbedding\Exceptions\UnsupportedEmbeddingProviderException(
                "Unsupported embedding provider: [{$value}]. Supported providers: "
                .implode(', ', array_column(self::cases(), 'value')),
            );
        }

        return $case;
    }
}
