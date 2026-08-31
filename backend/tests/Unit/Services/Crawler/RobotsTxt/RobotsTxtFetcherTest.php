<?php

namespace Tests\Unit\Services\Crawler\RobotsTxt;

use App\Services\Crawler\RobotsTxt\RobotsTxtFetcher;
use App\Services\Crawler\RobotsTxt\RobotsTxtParser;
use App\Services\Crawler\Url\UrlNormalizer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RobotsTxtFetcherTest extends TestCase
{
    private RobotsTxtFetcher $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $parser = new RobotsTxtParser();
        $normalizer = new UrlNormalizer();
        $this->fetcher = new RobotsTxtFetcher($parser, $normalizer);
    }

    public function test_fetches_existing_robots_txt(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "User-agent: *\nDisallow: /admin",
                200
            ),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertSame('https://example.com/robots.txt', $result['url']);
        $this->assertSame(200, $result['status_code']);
        $this->assertTrue($result['exists']);
        $this->assertNotNull($result['content']);
        $this->assertCount(1, $result['rules']);
        $this->assertNull($result['error']);
    }

    public function test_handles_404_robots_txt(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 404),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertSame('https://example.com/robots.txt', $result['url']);
        $this->assertSame(404, $result['status_code']);
        $this->assertFalse($result['exists']);
        $this->assertNull($result['content']);
        $this->assertSame([], $result['rules']);
        $this->assertSame([], $result['sitemaps']);
        $this->assertNull($result['error']);
    }

    public function test_handles_500_server_error(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 500),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertSame(500, $result['status_code']);
        $this->assertFalse($result['exists']);
        $this->assertNull($result['content']);
        $this->assertNull($result['error']);
    }

    public function test_normalizes_start_url(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 200),
        ]);

        $result = $this->fetcher->fetch('example.com');

        // Should normalize to https://example.com/robots.txt
        $this->assertSame('https://example.com/robots.txt', $result['url']);
    }

    public function test_handles_url_with_trailing_slash(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 200),
        ]);

        $result = $this->fetcher->fetch('https://example.com/');

        $this->assertSame('https://example.com/robots.txt', $result['url']);
    }

    public function test_extracts_sitemaps_from_robots_txt(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "User-agent: *\nDisallow:\n\nSitemap: https://example.com/sitemap.xml",
                200
            ),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertCount(1, $result['sitemaps']);
        $this->assertSame('https://example.com/sitemap.xml', $result['sitemaps'][0]);
    }

    public function test_normalizes_sitemap_urls(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "Sitemap: https://example.com/sitemap.xml#fragment",
                200
            ),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        // Fragment should be removed by normalizer
        $this->assertSame('https://example.com/sitemap.xml', $result['sitemaps'][0]);
    }

    public function test_parses_multiple_user_agents(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "User-agent: Googlebot\nDisallow: /private\n\nUser-agent: *\nDisallow: /admin",
                200
            ),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertCount(2, $result['rules']);
        $this->assertSame('Googlebot', $result['rules'][0]['user_agent']);
        $this->assertSame('*', $result['rules'][1]['user_agent']);
    }

    public function test_handles_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
        });

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertNull($result['status_code']);
        $this->assertFalse($result['exists']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('Connection failed', $result['error']);
    }

    public function test_handles_empty_robots_txt(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 200),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertTrue($result['exists']);
        $this->assertSame('', $result['content']);
        $this->assertSame([], $result['rules']);
        $this->assertSame([], $result['sitemaps']);
    }

    public function test_stores_raw_content(): void
    {
        $robotsContent = "User-agent: *\nDisallow: /admin\n# Comment\nSitemap: https://example.com/sitemap.xml";
        
        Http::fake([
            'https://example.com/robots.txt' => Http::response($robotsContent, 200),
        ]);

        $result = $this->fetcher->fetch('https://example.com');

        $this->assertSame($robotsContent, $result['content']);
    }
}
