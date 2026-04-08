<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

use Illuminate\Database\Eloquent\Model;

final readonly class EmbeddingTargetData
{
    public function __construct(
        public ?string $type,
        public int|string|null $id,
        public ?string $namespace = null,
    ) {}

    public static function fromModel(Model $model, ?string $namespace = null): self
    {
        return new self(
            type: $model->getMorphClass(),
            id: $model->getKey(),
            namespace: $namespace,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function fromContext(array $context): ?self
    {
        $namespace = isset($context['namespace']) && is_scalar($context['namespace'])
            ? (string) $context['namespace']
            : null;
        $target = $context['target'] ?? null;

        if ($target instanceof self) {
            return new self($target->type, $target->id, $target->namespace ?? $namespace);
        }

        if ($target instanceof Model) {
            return self::fromModel($target, $namespace);
        }

        $type = $context['target_type'] ?? null;
        $id = $context['target_id'] ?? null;

        if (! is_string($type) || trim($type) === '') {
            return null;
        }

        if (! is_scalar($id) && $id !== null) {
            return null;
        }

        return new self(
            type: trim($type),
            id: is_scalar($id) ? (string) $id : null,
            namespace: $namespace,
        );
    }
}
