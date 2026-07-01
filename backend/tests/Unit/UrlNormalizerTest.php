<?php

namespace Tests\Unit;

use App\Services\Crawler\Url\UrlNormalizer;
use PHPUnit\Framework\TestCase;

class UrlNormalizerTest extends TestCase
{
    public function test_it_adds_https_to_start_url_without_scheme(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeStartUrl('example.com');

        $this->assertSame('https://example.com', $result);
    }

    public function test_it_removes_trailing_slash_from_start_url(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeStartUrl('https://example.com/');

        $this->assertSame('https://example.com', $result);
    }

    public function test_it_normalizes_root_relative_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/kontakt', 'https://example.com');

        $this->assertSame('https://example.com/kontakt', $result);
    }

    public function test_it_normalizes_relative_links_from_current_page(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('team', 'https://example.com/ueber-uns');

        $this->assertSame('https://example.com/team', $result);
    }

    public function test_it_removes_fragments_from_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/kontakt#formular', 'https://example.com');

        $this->assertSame('https://example.com/kontakt', $result);
    }

    public function test_it_ignores_anchor_only_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('#formular', 'https://example.com');

        $this->assertNull($result);
    }

    public function test_it_ignores_mailto_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('mailto:test@example.com', 'https://example.com');

        $this->assertNull($result);
    }

    public function test_it_detects_internal_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->isInternal(
            'https://example.com/kontakt',
            'https://example.com'
        );

        $this->assertTrue($result);
    }

    public function test_it_detects_external_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->isInternal(
            'https://external.com',
            'https://example.com'
        );

        $this->assertFalse($result);
    }
}