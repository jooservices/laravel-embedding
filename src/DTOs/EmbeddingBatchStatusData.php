<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\DTOs;

use DateTimeInterface;

final readonly class EmbeddingBatchStatusData
{
    public function __construct(
        public string $id,
        public ?string $targetType,
        public int|string|null $targetId,
        public ?string $namespace,
        public ?string $provider,
        public ?string $model,
        public string $status,
        public int $totalChunks,
        public int $completedChunks,
        public int $failedChunks,
        public bool $replaceExisting,
        public bool $skipIfUnchanged,
        public ?string $stagedBatchToken,
        public ?string $sourceFormat,
        public ?string $sourcePath,
        public ?string $summary,
        public ?DateTimeInterface $completedAt,
        public DateTimeInterface $createdAt,
        public DateTimeInterface $updatedAt,
    ) {}

    public function progressPercentage(): float
    {
        if ($this->totalChunks === 0) {
            return 0.0;
        }

        return round((($this->completedChunks + $this->failedChunks) / $this->totalChunks) * 100, 2);
    }
}
