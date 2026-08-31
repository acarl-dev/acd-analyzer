<?php

namespace Tests\Unit\Services\Crawler\Parsing;

use App\Services\Crawler\Parsing\CanonicalExtractor;
use App\Services\Crawler\Url\UrlNormalizer;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class CanonicalExtractorTest extends TestCase
{
    private CanonicalExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new CanonicalExtractor(new UrlNormalizer());
    }

    public function test_no_canonical(): void
    {
        $html = '<html><head><title>Test</title></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertNull($result['href']);
        $this->assertNull($result['url']);
        $this->assertSame(0, $result['count']);
    }

    public function test_self_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://example.com/page', $result['href']);
        $this->assertSame('https://example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_relative_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="../products"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/category/item');

        $result = $this->extractor->extract($crawler, 'https://example.com/category/item');

        $this->assertSame('../products', $result['href']);
        $this->assertSame('https://example.com/products', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_root_relative_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="/products/item"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/category/item');

        $result = $this->extractor->extract($crawler, 'https://example.com/category/item');

        $this->assertSame('/products/item', $result['href']);
        $this->assertSame('https://example.com/products/item', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_absolute_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/products/item"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/category/item');

        $result = $this->extractor->extract($crawler, 'https://example.com/category/item');

        $this->assertSame('https://example.com/products/item', $result['href']);
        $this->assertSame('https://example.com/products/item', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_protocol_relative_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="//example.com/products/item"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/category/item');

        $result = $this->extractor->extract($crawler, 'https://example.com/category/item');

        $this->assertSame('//example.com/products/item', $result['href']);
        $this->assertSame('https://example.com/products/item', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_canonical_with_query(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page?id=123"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page?id=123&ref=foo');

        $result = $this->extractor->extract($crawler, 'https://example.com/page?id=123&ref=foo');

        $this->assertSame('https://example.com/page?id=123', $result['href']);
        $this->assertSame('https://example.com/page?id=123', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_canonical_with_fragment(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page#section"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://example.com/page#section', $result['href']);
        // Fragment should be removed by normalizer
        $this->assertSame('https://example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_canonical_different_schema(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head></html>';
        $crawler = new Crawler($html, 'http://example.com/page');

        $result = $this->extractor->extract($crawler, 'http://example.com/page');

        $this->assertSame('https://example.com/page', $result['href']);
        $this->assertSame('https://example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_canonical_different_host(): void
    {
        $html = '<html><head><link rel="canonical" href="https://other.com/page"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://other.com/page', $result['href']);
        $this->assertSame('https://other.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_canonical_www_vs_non_www(): void
    {
        $html = '<html><head><link rel="canonical" href="https://www.example.com/page"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://www.example.com/page', $result['href']);
        $this->assertSame('https://www.example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_empty_href(): void
    {
        $html = '<html><head><link rel="canonical" href=""></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('', $result['href']);
        $this->assertNull($result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_multiple_canonicals(): void
    {
        $html = <<<HTML
<html>
<head>
    <link rel="canonical" href="https://example.com/first">
    <link rel="canonical" href="https://example.com/second">
</head>
</html>
HTML;
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        // First canonical should be used
        $this->assertSame('https://example.com/first', $result['href']);
        $this->assertSame('https://example.com/first', $result['url']);
        $this->assertSame(2, $result['count']);
    }

    public function test_case_insensitive_rel(): void
    {
        $html = '<html><head><link rel="Canonical" href="https://example.com/page"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://example.com/page', $result['href']);
        $this->assertSame('https://example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_canonical_with_alternate(): void
    {
        $html = '<html><head><link rel="canonical alternate" href="https://example.com/page"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://example.com/page', $result['href']);
        $this->assertSame('https://example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_href_before_rel(): void
    {
        $html = '<html><head><link href="https://example.com/page" rel="canonical"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('https://example.com/page', $result['href']);
        $this->assertSame('https://example.com/page', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_whitespace_only_href(): void
    {
        $html = '<html><head><link rel="canonical" href="   "></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('', $result['href']);
        $this->assertNull($result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_invalid_url_in_href(): void
    {
        $html = '<html><head><link rel="canonical" href="not a valid url"></head></html>';
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        $this->assertSame('not a valid url', $result['href']);
        // "not a valid url" is interpreted as a relative path and normalized
        $this->assertSame('https://example.com/not a valid url', $result['url']);
        $this->assertSame(1, $result['count']);
    }

    public function test_first_valid_canonical_used_when_first_is_empty(): void
    {
        $html = <<<HTML
<html>
<head>
    <link rel="canonical" href="">
    <link rel="canonical" href="https://example.com/valid">
</head>
</html>
HTML;
        $crawler = new Crawler($html, 'https://example.com/page');

        $result = $this->extractor->extract($crawler, 'https://example.com/page');

        // Second canonical should be used (first valid)
        $this->assertSame('https://example.com/valid', $result['href']);
        $this->assertSame('https://example.com/valid', $result['url']);
        $this->assertSame(2, $result['count']);
    }
}
