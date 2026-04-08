<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JOOservices\LaravelEmbedding\Contracts\EmbeddingManager;

/**
 * Background job to process and embed a large text batch without blocking the main request.
 */
class ProcessEmbeddingBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $text,
        public readonly array $context = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmbeddingManager $manager): void
    {
        $manager->chunkAndEmbed($this->text, $this->context);
    }
}
