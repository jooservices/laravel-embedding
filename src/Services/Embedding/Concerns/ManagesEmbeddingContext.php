<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Embedding\Concerns;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingTargetData;
use JOOservices\LaravelEmbedding\DTOs\EmbeddingVectorData;

trait ManagesEmbeddingContext
{
    /**
     * Guard against empty text input.
     *
     * @throws InvalidArgumentException
     */
    private function assertNotEmpty(string $text, string $message = ''): void
    {
        if (trim($text) === '') {
            throw new InvalidArgumentException(
                $message !== '' ? $message : 'Input text must not be empty.',
            );
        }
    }

    /**
     * Extract the optional Eloquent model target from the context array.
     * Convention: pass the model under the key "target".
     */
    private function extractTarget(array $context): Model|EmbeddingTargetData|null
    {
        $target = $context['target'] ?? null;

        if ($target instanceof Model) {
            return $target;
        }

        if ($target instanceof EmbeddingTargetData) {
            return $target;
        }

        return EmbeddingTargetData::fromContext($context);
    }

    /**
     * Strip reserved context keys and return the remainder as arbitrary metadata.
     */
    private function extractMeta(array $context): array
    {
        $reserved = [
            'target',
            'target_type',
            'target_id',
            'namespace',
            'chunk_size',
            'chunk_overlap',
            'batch_size',
            'replace_existing',
            'skip_if_unchanged',
            'queue_connection',
            'queue_name',
            'queue_tries',
            'queue_backoff',
            'queue_timeout',
            'concurrency_key',
            'batch_id',
            'staged_batch_token',
            'source_format',
            'source_path',
        ];

        return array_diff_key($context, array_flip($reserved));
    }

    private function shouldReplaceExisting(array $context): bool
    {
        return (bool) ($context['replace_existing'] ?? false);
    }

    private function shouldSkipUnchanged(array $context): bool
    {
        return (bool) ($context['skip_if_unchanged'] ?? false);
    }

    /**
     * @param  EmbeddingVectorData[]  $vectors
     * @param  array<string, mixed>  $meta
     */
    private function persistVectors(array $vectors, Model|EmbeddingTargetData|null $target, array $meta, bool $replaceExisting): void
    {
        if ($this->repository === null || empty($vectors)) {
            return;
        }

        if ($replaceExisting && $target !== null) {
            $this->repository->replaceForTarget($vectors, $target, $meta);

            return;
        }

        if (count($vectors) === 1) {
            $this->repository->store($vectors[0], $target, $meta);

            return;
        }

        $this->repository->storeBatch($vectors, $target, $meta);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveConcurrencyKey(array $context): ?string
    {
        if (isset($context['concurrency_key']) && is_string($context['concurrency_key']) && $context['concurrency_key'] !== '') {
            return $context['concurrency_key'];
        }

        $target = EmbeddingTargetData::fromContext($context);
        if ($target === null || $target->type === null || $target->id === null) {
            return null;
        }

        $key = $target->type.':'.$target->id;

        return $target->namespace === null ? $key : $target->namespace.':'.$key;
    }
}
