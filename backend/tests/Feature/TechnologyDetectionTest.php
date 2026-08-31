<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\DetectedTechnology;
use App\Models\Page;
use App\Models\Website;
use App\Services\Analyzer\TechnologyDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_wordpress_from_crawled_pages(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Home',
            'html' => '<html><head><meta name="generator" content="WordPress 6.8.2"></head><body><script src="/wp-content/themes/theme/script.js"></script></body></html>',
        ]);

        $detector = app(TechnologyDetector::class);
        $detector->detect($crawlRun);

        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'slug' => 'wordpress',
            'category' => 'cms',
            'confidence' => 'high',
            'version' => '6.8.2',
        ]);
    }

    public function test_detects_multiple_technologies_from_single_page(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Home',
            'html' => <<<'HTML'
                <html>
                <head>
                    <meta name="generator" content="WordPress 6.8">
                    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
                    <link rel="stylesheet" href="/wp-content/themes/theme/bootstrap.min.css">
                </head>
                <body>
                    <script src="https://www.googletagmanager.com/gtm.js?id=GTM-XXXX"></script>
                    <script src="/wp-content/themes/theme/bootstrap.bundle.min.js"></script>
                </body>
                </html>
                HTML,
        ]);

        $detector = app(TechnologyDetector::class);
        $detector->detect($crawlRun);

        // Should detect WordPress, Google Tag Manager, Google Fonts, and Bootstrap
        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'slug' => 'wordpress',
        ]);

        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'slug' => 'google-tag-manager',
        ]);

        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'slug' => 'google-fonts',
        ]);

        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'slug' => 'bootstrap',
        ]);
    }

    public function test_does_not_create_duplicate_technology_entries(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 2,
        ]);

        // Two pages both using WordPress
        Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Home',
            'html' => '<html><head><meta name="generator" content="WordPress 6.8"></head><body></body></html>',
        ]);

        Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/about',
            'status_code' => 200,
            'title' => 'About',
            'html' => '<html><head><meta name="generator" content="WordPress 6.8"></head><body></body></html>',
        ]);

        $detector = app(TechnologyDetector::class);
        $detector->detect($crawlRun);

        // Should only create one WordPress entry
        $wordpressDetections = DetectedTechnology::where('crawl_run_id', $crawlRun->id)
            ->where('slug', 'wordpress')
            ->count();

        $this->assertEquals(1, $wordpressDetections);

        // But it should note it was detected on 2 pages
        $technology = DetectedTechnology::where('crawl_run_id', $crawlRun->id)
            ->where('slug', 'wordpress')
            ->first();

        $this->assertEquals(2, $technology->detected_on_pages);
    }

    public function test_skips_low_confidence_detections(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        // Only weak WordPress signal (wp-json in content)
        Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Home',
            'html' => '<html><body>Check out the wp-json API</body></html>',
        ]);

        $detector = app(TechnologyDetector::class);
        $detector->detect($crawlRun);

        // Should not detect WordPress with only weak signal
        $this->assertDatabaseMissing('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'slug' => 'wordpress',
        ]);
    }

    public function test_stores_evidence_for_detections(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Home',
            'html' => '<html><head><meta name="generator" content="WordPress 6.8"></head><body></body></html>',
        ]);

        $detector = app(TechnologyDetector::class);
        $detector->detect($crawlRun);

        $technology = DetectedTechnology::where('crawl_run_id', $crawlRun->id)
            ->where('slug', 'wordpress')
            ->first();

        $this->assertNotNull($technology->evidence);
        $this->assertIsArray($technology->evidence);
        $this->assertGreaterThan(0, count($technology->evidence));
    }
}
