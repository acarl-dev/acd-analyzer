<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\RobotsTxt;
use App\Models\Website;
use App\Services\Crawler\RobotsTxt\RobotsTxtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RobotsTxtPersistenceTest extends TestCase
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

    public function test_stores_robots_txt_with_200_status(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "User-agent: *\nDisallow: /admin\n\nSitemap: https://example.com/sitemap.xml",
                200
            ),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertDatabaseHas('robots_txt', [
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/robots.txt',
            'status_code' => 200,
            'exists' => true,
        ]);

        $this->assertNotNull($robotsTxt->content);
        $this->assertNotNull($robotsTxt->fetched_at);
        $this->assertCount(1, $robotsTxt->rules);
        $this->assertSame('*', $robotsTxt->rules[0]['user_agent']);
        $this->assertSame(['/admin'], $robotsTxt->rules[0]['disallow']);
        $this->assertCount(1, $robotsTxt->sitemaps);
        $this->assertSame('https://example.com/sitemap.xml', $robotsTxt->sitemaps[0]);
    }

    public function test_stores_404_robots_txt(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 404),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertDatabaseHas('robots_txt', [
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'status_code' => 404,
            'exists' => false,
        ]);

        $this->assertNull($robotsTxt->content);
        $this->assertEmpty($robotsTxt->rules);
        $this->assertEmpty($robotsTxt->sitemaps);
    }

    public function test_stores_500_server_error(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response('Internal Server Error', 500),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertDatabaseHas('robots_txt', [
            'website_id' => $website->id,
            'status_code' => 500,
            'exists' => false,
        ]);
    }

    public function test_stores_multiple_sitemaps(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "Sitemap: https://example.com/sitemap.xml\nSitemap: https://example.com/sitemap-news.xml",
                200
            ),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertCount(2, $robotsTxt->sitemaps);
        $this->assertContains('https://example.com/sitemap.xml', $robotsTxt->sitemaps);
        $this->assertContains('https://example.com/sitemap-news.xml', $robotsTxt->sitemaps);
    }

    public function test_stores_multiple_user_agent_rules(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "User-agent: Googlebot\nDisallow: /private\n\nUser-agent: *\nDisallow: /admin",
                200
            ),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertCount(2, $robotsTxt->rules);
        $this->assertSame('Googlebot', $robotsTxt->rules[0]['user_agent']);
        $this->assertSame(['/private'], $robotsTxt->rules[0]['disallow']);
        $this->assertSame('*', $robotsTxt->rules[1]['user_agent']);
        $this->assertSame(['/admin'], $robotsTxt->rules[1]['disallow']);
    }

    public function test_stores_raw_content_for_manual_review(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        $rawContent = "# Custom robots.txt\nUser-agent: *\nDisallow: /secret\nCrawl-delay: 10";

        Http::fake([
            'https://example.com/robots.txt' => Http::response($rawContent, 200),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertSame($rawContent, $robotsTxt->content);
    }

    public function test_deletes_robots_txt_when_crawl_run_deleted(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response('User-agent: *', 200),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertDatabaseHas('robots_txt', ['id' => $robotsTxt->id]);

        $crawlRun->delete();

        $this->assertDatabaseMissing('robots_txt', ['id' => $robotsTxt->id]);
    }

    public function test_stores_empty_robots_txt(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website->id);

        Http::fake([
            'https://example.com/robots.txt' => Http::response('', 200),
        ]);

        $service = app(RobotsTxtService::class);
        $robotsTxt = $service->fetchAndStore($website, $crawlRun);

        $this->assertTrue($robotsTxt->exists);
        $this->assertSame('', $robotsTxt->content);
        $this->assertEmpty($robotsTxt->rules);
        $this->assertEmpty($robotsTxt->sitemaps);
    }
}
