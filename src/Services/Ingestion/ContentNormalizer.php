<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Services\Ingestion;

use InvalidArgumentException;

final class ContentNormalizer
{
    public function normalizeMarkdown(string $markdown): string
    {
        $text = preg_replace('/```.*?```/su', ' ', $markdown) ?? $markdown;
        $text = preg_replace('/`([^`]+)`/u', '$1', $text) ?? $text;
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $text) ?? $text;
        $text = preg_replace('/^\s{0,3}#{1,6}\s*/mu', '', $text) ?? $text;
        $text = preg_replace('/^\s*[-*+]\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/^\s*\d+\.\s+/mu', '', $text) ?? $text;
        $text = str_replace(['**', '__', '*', '_'], '', $text);

        return $this->normalizeWhitespace($text);
    }

    public function normalizeHtml(string $html): string
    {
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text) ?? $text;
        $text = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])\b[^>]*>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->normalizeWhitespace($text);
    }

    public function normalizePlainText(string $text): string
    {
        return $this->normalizeWhitespace($text);
    }

    /**
     * @return array{content: string, format: string, path: string}
     */
    public function normalizeFile(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File [{$path}] does not exist or is not readable.");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException("Unable to read file [{$path}].");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $format = match ($extension) {
            'md', 'markdown' => 'markdown',
            'html', 'htm' => 'html',
            default => 'text',
        };

        $normalized = match ($format) {
            'markdown' => $this->normalizeMarkdown($contents),
            'html' => $this->normalizeHtml($contents),
            default => $this->normalizePlainText($contents),
        };

        return [
            'content' => $normalized,
            'format' => $format,
            'path' => $path,
        ];
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
