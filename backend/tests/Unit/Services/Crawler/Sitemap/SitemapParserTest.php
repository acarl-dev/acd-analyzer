<?php

namespace Tests\Unit\Services\Crawler\Sitemap;

use App\Services\Crawler\Sitemap\SitemapParser;
use Tests\TestCase;

class SitemapParserTest extends TestCase
{
    private SitemapParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SitemapParser();
    }

    public function test_parses_empty_content(): void
    {
        $result = $this->parser->parse('');

        $this->assertSame('unknown', $result['type']);
        $this->assertSame([], $result['urls']);
        $this->assertSame([], $result['sitemaps']);
        $this->assertNotNull($result['error']);
    }

    public function test_parses_simple_urlset(): void
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
    <url>
        <loc>https://example.com/page2</loc>
    </url>
</urlset>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('urlset', $result['type']);
        $this->assertCount(2, $result['urls']);
        $this->assertSame('https://example.com/page1', $result['urls'][0]['loc']);
        $this->assertSame('2026-08-30', $result['urls'][0]['lastmod']);
        $this->assertSame('weekly', $result['urls'][0]['changefreq']);
        $this->assertSame(0.8, $result['urls'][0]['priority']);
        $this->assertSame('https://example.com/page2', $result['urls'][1]['loc']);
        $this->assertNull($result['urls'][1]['lastmod']);
        $this->assertNull($result['error']);
    }

    public function test_parses_urlset_without_namespace(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset>
    <url>
        <loc>https://example.com/page1</loc>
    </url>
</urlset>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('urlset', $result['type']);
        $this->assertCount(1, $result['urls']);
        $this->assertSame('https://example.com/page1', $result['urls'][0]['loc']);
    }

    public function test_parses_sitemap_index(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap1.xml</loc>
        <lastmod>2026-08-30</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap2.xml</loc>
    </sitemap>
</sitemapindex>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('index', $result['type']);
        $this->assertCount(2, $result['sitemaps']);
        $this->assertSame('https://example.com/sitemap1.xml', $result['sitemaps'][0]['loc']);
        $this->assertSame('2026-08-30', $result['sitemaps'][0]['lastmod']);
        $this->assertSame('https://example.com/sitemap2.xml', $result['sitemaps'][1]['loc']);
        $this->assertNull($result['sitemaps'][1]['lastmod']);
        $this->assertNull($result['error']);
    }

    public function test_parses_sitemap_index_without_namespace(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex>
    <sitemap>
        <loc>https://example.com/sitemap1.xml</loc>
    </sitemap>
</sitemapindex>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('index', $result['type']);
        $this->assertCount(1, $result['sitemaps']);
        $this->assertSame('https://example.com/sitemap1.xml', $result['sitemaps'][0]['loc']);
    }

    public function test_handles_invalid_xml(): void
    {
        $xml = '<invalid><xml';

        $result = $this->parser->parse($xml);

        $this->assertSame('unknown', $result['type']);
        $this->assertSame([], $result['urls']);
        $this->assertSame([], $result['sitemaps']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('Invalid XML', $result['error']);
    }

    public function test_handles_unknown_root_element(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<unknown>
    <element>test</element>
</unknown>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('unknown', $result['type']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('Unknown sitemap format', $result['error']);
    }

    public function test_skips_urls_without_loc(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <lastmod>2026-08-30</lastmod>
    </url>
    <url>
        <loc>https://example.com/page1</loc>
    </url>
</urlset>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('urlset', $result['type']);
        $this->assertCount(1, $result['urls']);
        $this->assertSame('https://example.com/page1', $result['urls'][0]['loc']);
    }

    public function test_skips_sitemaps_without_loc(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <lastmod>2026-08-30</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap1.xml</loc>
    </sitemap>
</sitemapindex>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('index', $result['type']);
        $this->assertCount(1, $result['sitemaps']);
        $this->assertSame('https://example.com/sitemap1.xml', $result['sitemaps'][0]['loc']);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/page1</loc>
    </url>
</urlset>
XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result['urls']);
        $this->assertNull($result['urls'][0]['lastmod']);
        $this->assertNull($result['urls'][0]['changefreq']);
        $this->assertNull($result['urls'][0]['priority']);
    }

    public function test_parses_priority_as_float(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/page1</loc>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>https://example.com/page2</loc>
        <priority>1.0</priority>
    </url>
</urlset>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame(0.5, $result['urls'][0]['priority']);
        $this->assertSame(1.0, $result['urls'][1]['priority']);
    }

    public function test_handles_real_world_wordpress_sitemap(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <url>
        <loc>https://example.com/</loc>
        <lastmod>2026-08-30T14:30:00+00:00</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://example.com/about/</loc>
        <lastmod>2026-08-25T10:15:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('urlset', $result['type']);
        $this->assertCount(2, $result['urls']);
        $this->assertSame('https://example.com/', $result['urls'][0]['loc']);
        $this->assertSame('2026-08-30T14:30:00+00:00', $result['urls'][0]['lastmod']);
        $this->assertSame('daily', $result['urls'][0]['changefreq']);
        $this->assertSame(1.0, $result['urls'][0]['priority']);
    }

    public function test_handles_real_world_sitemap_index(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/post-sitemap.xml</loc>
        <lastmod>2026-08-30T12:00:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/page-sitemap.xml</loc>
        <lastmod>2026-08-29T08:30:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/product-sitemap.xml</loc>
        <lastmod>2026-08-28T16:45:00+00:00</lastmod>
    </sitemap>
</sitemapindex>
XML;

        $result = $this->parser->parse($xml);

        $this->assertSame('index', $result['type']);
        $this->assertCount(3, $result['sitemaps']);
        $this->assertSame('https://example.com/post-sitemap.xml', $result['sitemaps'][0]['loc']);
        $this->assertSame('https://example.com/page-sitemap.xml', $result['sitemaps'][1]['loc']);
        $this->assertSame('https://example.com/product-sitemap.xml', $result['sitemaps'][2]['loc']);
    }
}
