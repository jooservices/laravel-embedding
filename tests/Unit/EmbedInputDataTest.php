<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use JOOservices\LaravelEmbedding\DTOs\EmbedInputData;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class EmbedInputDataTest extends TestCase
{
    public function test_from_text(): void
    {
        $data = EmbedInputData::fromText('hello', ['key' => 'val']);
        $this->assertSame('hello', $data->chunk->content);
        $this->assertSame(['key' => 'val'], $data->context);
    }
}
