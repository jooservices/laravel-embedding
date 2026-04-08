<?php

declare(strict_types=1);

namespace JOOservices\LaravelEmbedding\Tests\Unit;

use InvalidArgumentException;
use JOOservices\LaravelEmbedding\Services\Ingestion\ContentNormalizer;
use JOOservices\LaravelEmbedding\Tests\TestCase;

final class ContentNormalizerTest extends TestCase
{
    public function test_normalize_markdown_strips_common_markdown_syntax(): void
    {
        $normalizer = new ContentNormalizer;

        $result = $normalizer->normalizeMarkdown(<<<'MD'
        # Title

        Some **bold** text with a [link](https://example.com) and `inline code`.

        - First item
        1. Numbered item

        ```php
        echo 'hidden';
        ```

        ![Diagram](diagram.png)
        MD);

        $this->assertStringContainsString('Title', $result);
        $this->assertStringContainsString('Some bold text with a link and inline code.', $result);
        $this->assertStringContainsString('First item', $result);
        $this->assertStringContainsString('Numbered item', $result);
        $this->assertStringContainsString('Diagram', $result);
        $this->assertStringNotContainsString('```', $result);
        $this->assertStringNotContainsString('https://example.com', $result);
    }

    public function test_normalize_html_strips_tags_scripts_and_styles(): void
    {
        $normalizer = new ContentNormalizer;

        $result = $normalizer->normalizeHtml(<<<'HTML'
        <html>
            <head>
                <style>.hidden { display:none; }</style>
                <script>window.alert('ignore');</script>
            </head>
            <body>
                <h1>Heading</h1>
                <p>Hello &amp; welcome<br>friend</p>
            </body>
        </html>
        HTML);

        $this->assertStringContainsString('Heading', $result);
        $this->assertStringContainsString('Hello & welcome', $result);
        $this->assertStringContainsString('friend', $result);
        $this->assertStringNotContainsString('window.alert', $result);
        $this->assertStringNotContainsString('.hidden', $result);
    }

    public function test_normalize_plain_text_collapses_whitespace(): void
    {
        $normalizer = new ContentNormalizer;

        $result = $normalizer->normalizePlainText("  hello   world \r\n\r\n\r\n next\t\tline ");

        $this->assertSame("hello world\n\n next line", $result);
    }

    public function test_normalize_file_detects_supported_formats(): void
    {
        $normalizer = new ContentNormalizer;

        $markdownPath = tempnam(sys_get_temp_dir(), 'embedding-md');
        $htmlPath = tempnam(sys_get_temp_dir(), 'embedding-html');
        $textPath = tempnam(sys_get_temp_dir(), 'embedding-text');

        if ($markdownPath === false || $htmlPath === false || $textPath === false) {
            $this->fail('Unable to create temporary files for ingestion tests.');
        }

        $realMarkdownPath = $markdownPath.'.md';
        $realHtmlPath = $htmlPath.'.html';
        $realTextPath = $textPath.'.txt';

        rename($markdownPath, $realMarkdownPath);
        rename($htmlPath, $realHtmlPath);
        rename($textPath, $realTextPath);

        file_put_contents($realMarkdownPath, "# Title\n\nBody");
        file_put_contents($realHtmlPath, '<p>Hello <strong>world</strong></p>');
        file_put_contents($realTextPath, " plain \n\n text ");

        try {
            $markdown = $normalizer->normalizeFile($realMarkdownPath);
            $html = $normalizer->normalizeFile($realHtmlPath);
            $text = $normalizer->normalizeFile($realTextPath);

            $this->assertSame('markdown', $markdown['format']);
            $this->assertSame('Title'."\n\n".'Body', $markdown['content']);
            $this->assertSame($realMarkdownPath, $markdown['path']);

            $this->assertSame('html', $html['format']);
            $this->assertSame('Hello world', $html['content']);

            $this->assertSame('text', $text['format']);
            $this->assertSame("plain\n\n text", $text['content']);
        } finally {
            @unlink($realMarkdownPath);
            @unlink($realHtmlPath);
            @unlink($realTextPath);
        }
    }

    public function test_normalize_file_throws_for_missing_path(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ContentNormalizer)->normalizeFile('/path/that/does/not/exist.md');
    }
}
