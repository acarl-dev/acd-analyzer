<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\RobotsTxt;
use App\Models\Sitemap;
use App\Models\SitemapUrl;
use App\Models\Website;
use App\Services\Crawler\Sitemap\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SitemapPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function createWebsite(string $url = 'https://example.com'): Website
    {
        return Website::forceCreate(['url' => $url, 'host' => parse_url($url, PHP_URL_HOST)]);
    }

    private function createCrawlRun(int $websiteId): CrawlRun
    {
        return CrawlRun::forceCreate(['website_id' => $websiteId, 'status' => 'completed']);
    }

    public function test_stores_sitemap_from_robots_txt(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => ['https://example.com/sitemap.xml'],
            'fetched_at' => now(),
        ]);

        $xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        $this->assertCount(1, $sitemaps);
        $this->assertDatabaseHas('sitemaps', [
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/sitemap.xml',
            'status_code' => 200,
            'type' => 'urlset',
            'exists' => true,
        ]);
    }

    public function test_stores_sitemap_urls(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => ['https://example.com/sitemap.xml'],
            'fetched_at' => now(),
        ]);

        $xml = <<<XML
<urlset>
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

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        $sitemap = $sitemaps[0];

        $this->assertDatabaseHas('sitemap_urls', [
            'sitemap_id' => $sitemap->id,
            'url' => 'https://example.com/page1',
            'normalized_url' => 'https://example.com/page1',
            'changefreq' => 'weekly',
            'priority' => 0.8,
        ]);

        $this->assertDatabaseHas('sitemap_urls', [
            'sitemap_id' => $sitemap->id,
            'url' => 'https://example.com/page2',
            'normalized_url' => 'https://example.com/page2',
        ]);

        $this->assertSame(2, SitemapUrl::where('sitemap_id', $sitemap->id)->count());
    }

    public function test_stores_nested_sitemap_index(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => ['https://example.com/sitemap_index.xml'],
            'fetched_at' => now(),
        ]);

        $indexXml = <<<XML
<sitemapindex>
    <sitemap><loc>https://example.com/sitemap1.xml</loc></sitemap>
</sitemapindex>
XML;

        $sitemap1Xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap_index.xml' => Http::response($indexXml, 200),
            'https://example.com/sitemap1.xml' => Http::response($sitemap1Xml, 200),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        // Root sitemap index
        $this->assertDatabaseHas('sitemaps', [
            'website_id' => $website->id,
            'url' => 'https://example.com/sitemap_index.xml',
            'type' => 'index',
            'parent_sitemap_id' => null,
        ]);

        // Child sitemap
        $parentSitemap = Sitemap::where('url', 'https://example.com/sitemap_index.xml')->first();
        $this->assertDatabaseHas('sitemaps', [
            'website_id' => $website->id,
            'url' => 'https://example.com/sitemap1.xml',
            'type' => 'urlset',
            'parent_sitemap_id' => $parentSitemap->id,
        ]);
    }

    public function test_stores_404_sitemap(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => ['https://example.com/sitemap.xml'],
            'fetched_at' => now(),
        ]);

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response('', 404),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        $this->assertDatabaseHas('sitemaps', [
            'website_id' => $website->id,
            'status_code' => 404,
            'exists' => false,
        ]);
    }

    public function test_uses_fallback_when_no_robots_txt(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        $xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
            'https://example.com/sitemap_index.xml' => Http::response('', 404),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        // Should find sitemap.xml via fallback
        $this->assertGreaterThan(0, count($sitemaps));
    }

    public function test_normalizes_sitemap_urls(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => ['https://example.com/sitemap.xml'],
            'fetched_at' => now(),
        ]);

        $xml = '<urlset><url><loc>https://example.com/page#fragment</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        $sitemap = $sitemaps[0];

        $this->assertDatabaseHas('sitemap_urls', [
            'sitemap_id' => $sitemap->id,
            'url' => 'https://example.com/page#fragment',
            'normalized_url' => 'https://example.com/page',
        ]);
    }

    public function test_deletes_sitemaps_when_crawl_run_deleted(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => ['https://example.com/sitemap.xml'],
            'fetched_at' => now(),
        ]);

        $xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap.xml' => Http::response($xml, 200),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        $sitemapId = $sitemaps[0]->id;

        $this->assertDatabaseHas('sitemaps', ['id' => $sitemapId]);
        $this->assertDatabaseHas('sitemap_urls', ['sitemap_id' => $sitemapId]);

        $crawlRun->delete();

        $this->assertDatabaseMissing('sitemaps', ['id' => $sitemapId]);
        $this->assertDatabaseMissing('sitemap_urls', ['sitemap_id' => $sitemapId]);
    }

    public function test_stores_multiple_sitemaps_from_robots_txt(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
            'sitemaps' => [
                'https://example.com/sitemap1.xml',
                'https://example.com/sitemap2.xml',
            ],
            'fetched_at' => now(),
        ]);

        $xml = '<urlset><url><loc>https://example.com/page1</loc></url></urlset>';

        Http::fake([
            'https://example.com/sitemap1.xml' => Http::response($xml, 200),
            'https://example.com/sitemap2.xml' => Http::response($xml, 200),
        ]);

        $service = app(SitemapService::class);
        $sitemaps = $service->fetchAndStore($website, $crawlRun);

        $this->assertCount(2, $sitemaps);
        $this->assertDatabaseHas('sitemaps', ['url' => 'https://example.com/sitemap1.xml']);
        $this->assertDatabaseHas('sitemaps', ['url' => 'https://example.com/sitemap2.xml']);
    }
}
