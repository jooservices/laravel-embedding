<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Providers\Ollama;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JOOservices\LaravelEmbedding\Exceptions\EmbeddingFailedException;

/**
 * Low-level HTTP client for the Ollama REST API.
 *
 * Responsibility: send outbound requests to the configured Ollama base URL
 * and return raw response arrays. All provider-level concerns (model,
 * request shaping, response normalization) are handled by the adapter layer.
 */
class OllamaClient implements OllamaClientInterface
{
    private string $baseUrl;

    private int $timeout;

    public function __construct(string $baseUrl, int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * POST to the Ollama /embed endpoint.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws EmbeddingFailedException
     */
    public function embed(array $payload): array
    {
        return $this->post('/embed', $payload);
    }

    /**
     * Send a POST request to the given API path.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws EmbeddingFailedException
     */
    private function post(string $path, array $payload): array
    {
        $url = $this->baseUrl.$path;

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw EmbeddingFailedException::fromProviderError(
                provider: 'ollama',
                reason: "Connection failed: {$e->getMessage()}",
            );
        }

        $this->assertSuccessful($response, $url);

        return $response->json() ?? [];
    }

    /**
     * Assert that the response was successful; throw on any HTTP error.
     *
     * @throws EmbeddingFailedException
     */
    private function assertSuccessful(Response $response, string $url): void
    {
        if ($response->failed()) {
            throw EmbeddingFailedException::fromProviderError(
                provider: 'ollama',
                reason: "HTTP {$response->status()} from [{$url}]: {$response->body()}",
                code: $response->status(),
            );
        }
    }
}
