<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\Ollama;

/**
 * Contract for the Ollama HTTP transport layer.
 * Extracted as an interface to enable testing without HTTP calls.
 */
interface OllamaClientInterface
{
    /**
     * POST to the Ollama /embed endpoint.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws \JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException
     */
    public function embed(array $payload): array;
}
