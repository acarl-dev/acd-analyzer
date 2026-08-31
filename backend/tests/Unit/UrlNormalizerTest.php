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

    public function test_it_normalizes_internal_http_links_to_the_start_url_scheme(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink(
            'http://example.com/kontakt',
            'https://example.com'
        );

        $this->assertSame('https://example.com/kontakt', $result);
    }

    public function test_it_ignores_email_like_links_without_mailto_scheme(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink(
            'info@example.com',
            'https://example.com'
        );

        $this->assertNull($result);
    }

    public function test_it_treats_www_and_non_www_hosts_as_internal(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertTrue(
            $normalizer->isInternal(
                'https://www.example.com/kontakt',
                'https://example.com'
            )
        );

        $this->assertTrue(
            $normalizer->isInternal(
                'https://example.com/kontakt',
                'https://www.example.com'
            )
        );
    }

    public function test_it_does_not_treat_mailto_links_as_internal(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertFalse(
            $normalizer->isInternal(
                'mailto:test@example.com',
                'https://example.com'
            )
        );
    }

    // M1.1 Tests: RFC-compliant relative URL resolution

    public function test_it_normalizes_parent_directory_relative_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('../contact', 'https://example.com/about/team');

        $this->assertSame('https://example.com/contact', $result);
    }

    public function test_it_normalizes_current_directory_relative_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('./contact', 'https://example.com/about');

        $this->assertSame('https://example.com/contact', $result);
    }

    public function test_it_preserves_query_parameters(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/search?q=test', 'https://example.com');

        $this->assertSame('https://example.com/search?q=test', $result);
    }

    public function test_it_ignores_fragment_only_urls(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('#section', 'https://example.com/page');

        $this->assertNull($result);
    }

    public function test_it_normalizes_protocol_relative_urls(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('//example.de/contact', 'https://example.com');

        $this->assertSame('https://example.de/contact', $result);
    }

    public function test_it_preserves_http_scheme_for_external_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('http://external.com/page', 'https://example.com');

        $this->assertSame('http://external.com/page', $result);
    }

    public function test_it_preserves_https_scheme_for_external_links(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('https://external.com/page', 'https://example.com');

        $this->assertSame('https://external.com/page', $result);
    }

    public function test_it_detects_external_domains_as_not_internal(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertFalse(
            $normalizer->isInternal('https://external.com/page', 'https://example.com')
        );
    }

    public function test_it_normalizes_custom_ports(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/contact', 'https://example.com:8080');

        $this->assertSame('https://example.com:8080/contact', $result);
    }

    public function test_it_removes_default_ports(): void
    {
        $normalizer = new UrlNormalizer();

        // HTTP default port 80 - internal link, so scheme converts to base URL scheme
        $result = $normalizer->normalizeLink('http://example.com:80/page', 'https://example.com');
        $this->assertSame('https://example.com/page', $result);

        // HTTPS default port 443
        $result = $normalizer->normalizeLink('https://example.com:443/page', 'https://example.com');
        $this->assertSame('https://example.com/page', $result);
        
        // External link with default port - scheme preserved
        $result = $normalizer->normalizeLink('http://external.com:80/page', 'https://example.com');
        $this->assertSame('http://external.com/page', $result);
    }

    public function test_it_removes_trailing_slash_from_paths(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/contact/', 'https://example.com');

        $this->assertSame('https://example.com/contact', $result);
    }

    public function test_it_removes_trailing_slash_from_root(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/', 'https://example.com/page');

        $this->assertSame('https://example.com', $result);
    }

    public function test_it_removes_fragments_but_keeps_query_parameters(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('/contact?ref=footer#form', 'https://example.com');

        $this->assertSame('https://example.com/contact?ref=footer', $result);
    }

    public function test_it_normalizes_hosts_to_lowercase(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('https://Example.COM/Page', 'https://example.com');

        $this->assertSame('https://example.com/Page', $result);
    }

    public function test_www_and_non_www_are_treated_as_same_for_internal_classification(): void
    {
        $normalizer = new UrlNormalizer();

        $this->assertTrue(
            $normalizer->isInternal('https://www.example.com/page', 'https://example.com')
        );

        $this->assertTrue(
            $normalizer->isInternal('https://example.com/page', 'https://www.example.com')
        );
    }

    public function test_it_handles_complex_relative_paths_with_multiple_parent_directories(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink(
            '../../contact',
            'https://example.com/a/b/c/page'
        );

        $this->assertSame('https://example.com/a/contact', $result);
    }

    public function test_it_normalizes_tel_links_to_null(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('tel:+49123456789', 'https://example.com');

        $this->assertNull($result);
    }

    public function test_it_normalizes_javascript_links_to_null(): void
    {
        $normalizer = new UrlNormalizer();

        $result = $normalizer->normalizeLink('javascript:void(0)', 'https://example.com');

        $this->assertNull($result);
    }
}