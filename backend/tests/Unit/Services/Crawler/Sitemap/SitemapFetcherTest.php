<?php

namespace Tests\Unit\Services\Crawler\Sitemap;

use App\Services\Crawler\Download\PageDownloader;
use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Crawler\Sitemap\SitemapFetcher;
use App\Services\Crawler\Sitemap\SitemapParser;
use App\Services\Crawler\Url\UrlNormalizer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SitemapFetcherTest extends TestCase
{
    private SitemapFetcher $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $normalizer = new UrlNormalizer();
        $downloader = new PageDownloader($normalizer);
        $parser = new SitemapParser();
        
        $this->fetcher = new SitemapFetcher($downloader, $parser, $normalizer);
    }

    public function test_fetches_simple_urlset(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/page1</loc>
        <lastmod>2026-08-30</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
XML;

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertSame('https://example.com/sitemap.xml', $result['url']);
        $this->assertSame(200, $result['status_code']);
        $this->assertSame('urlset', $result['type']);
        $this->assertTrue($result['exists']);
        $this->assertNull($result['error']);
        $this->assertCount(1, $result['urls']);
        $this->assertSame('https://example.com/page1', $result['urls'][0]['url']);
        $this->assertSame('https://example.com/page1', $result['urls'][0]['normalized_url']);
    }

    public function test_handles_404(): void
    {
        Http::fake([
            'https://example.com/sitemap.xml' => Http::response('', 404),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertSame(404, $result['status_code']);
        $this->assertFalse($result['exists']);
        $this->assertSame('Not found', $result['error']);
    }

    public function test_handles_500_error(): void
    {
        Http::fake([
            'https://example.com/sitemap.xml' => Http::response('', 500),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertSame(500, $result['status_code']);
        $this->assertFalse($result['exists']);
        $this->assertSame('HTTP 500', $result['error']);
    }

    public function test_normalizes_sitemap_url(): void
    {
        $xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('example.com/sitemap.xml');

        // Should normalize to https://example.com/sitemap.xml
        $this->assertStringContainsString('https://example.com', $result['url']);
    }

    public function test_normalizes_url_entries(): void
    {
        $xml = <<<XML
<urlset>
    <url><loc>https://example.com/page1#fragment</loc></url>
</urlset>
XML;

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        // Fragment should be removed by normalizer
        $this->assertSame('https://example.com/page1#fragment', $result['urls'][0]['url']);
        $this->assertSame('https://example.com/page1', $result['urls'][0]['normalized_url']);
    }

    public function test_fetches_sitemap_index_recursively(): void
    {
        $indexXml = <<<XML
<sitemapindex>
    <sitemap><loc>https://example.com/sitemap1.xml</loc></sitemap>
    <sitemap><loc>https://example.com/sitemap2.xml</loc></sitemap>
</sitemapindex>
XML;

        $sitemap1Xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';
        $sitemap2Xml = '<urlset><url><loc>https://example.com/page2</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap_index.xml' => Http::response($indexXml, 200),
            'https://example.com/sitemap1.xml' => Http::response($sitemap1Xml, 200),
            'https://example.com/sitemap2.xml' => Http::response($sitemap2Xml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap_index.xml');

        $this->assertSame('index', $result['type']);
        $this->assertCount(2, $result['child_sitemaps']);
        $this->assertSame('https://example.com/sitemap1.xml', $result['child_sitemaps'][0]['url']);
        $this->assertSame('https://example.com/sitemap2.xml', $result['child_sitemaps'][1]['url']);
        $this->assertCount(1, $result['child_sitemaps'][0]['urls']);
        $this->assertCount(1, $result['child_sitemaps'][1]['urls']);
    }

    public function test_respects_max_sitemap_depth(): void
    {
        $indexXml = <<<XML
<sitemapindex>
    <sitemap><loc>https://example.com/level1.xml</loc></sitemap>
</sitemapindex>
XML;

        Http::fake([
            'https://example.com/sitemap_index.xml' => Http::response($indexXml, 200),
            'https://example.com/level1.xml' => Http::response($indexXml, 200),
            'https://example.com/level2.xml' => Http::response($indexXml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap_index.xml');

        // Should stop at MAX_DEPTH (3)
        // Depth 0: sitemap_index.xml
        // Depth 1: level1.xml
        // Depth 2: level1.xml (recursive)
        // Depth 3: MAX_DEPTH reached, stops
        
        $this->assertLessThanOrEqual(SitemapFetcher::MAX_SITEMAP_DEPTH + 1, $this->fetcher->getFetchedSitemapCount());
    }

    public function test_counts_fetched_sitemaps(): void
    {
        $indexXml = <<<XML
<sitemapindex>
    <sitemap><loc>https://example.com/sitemap1.xml</loc></sitemap>
    <sitemap><loc>https://example.com/sitemap2.xml</loc></sitemap>
</sitemapindex>
XML;

        $urlsetXml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap_index.xml' => Http::response($indexXml, 200),
            'https://example.com/sitemap1.xml' => Http::response($urlsetXml, 200),
            'https://example.com/sitemap2.xml' => Http::response($urlsetXml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap_index.xml');

        $this->assertSame(3, $this->fetcher->getFetchedSitemapCount());
    }

    public function test_counts_total_urls(): void
    {
        $xml = <<<XML
<urlset>
    <url><loc>https://example.com/page1</loc></url>
    <url><loc>https://example.com/page2</loc></url>
    <url><loc>https://example.com/page3</loc></url>
</urlset>
XML;

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertSame(3, $this->fetcher->getTotalUrlCount());
    }

    public function test_handles_invalid_xml(): void
    {
        Http::fake([
            'https://example.com/sitemap.xml' => Http::response('<invalid><xml', 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertTrue($result['exists']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('Invalid XML', $result['error']);
    }

    public function test_handles_html_instead_of_xml(): void
    {
        Http::fake([
            'https://example.com/sitemap.xml' => Http::response('<html><body>Not a sitemap</body></html>', 200),
        ]);

        $this->fetcher->resetCounters();
        $result = $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertTrue($result['exists']);
        $this->assertNotNull($result['error']);
    }

    public function test_reset_counters(): void
    {
        $xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $this->fetcher->resetCounters();
        $this->fetcher->fetch('https://example.com/sitemap.xml');

        $this->assertSame(1, $this->fetcher->getFetchedSitemapCount());
        $this->assertSame(1, $this->fetcher->getTotalUrlCount());

        $this->fetcher->resetCounters();

        $this->assertSame(0, $this->fetcher->getFetchedSitemapCount());
        $this->assertSame(0, $this->fetcher->getTotalUrlCount());
    }
}
