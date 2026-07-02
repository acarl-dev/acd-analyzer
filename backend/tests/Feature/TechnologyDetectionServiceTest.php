<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\DetectedTechnology;
use App\Models\Page;
use App\Models\Website;
use App\Services\Analyzer\TechnologyDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_detected_technologies_for_crawl_pages(): void
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
            'title' => 'Example',
            'meta_description' => null,
            'html' => '<html><head><link href="/wp-content/themes/theme/style.css"></head></html>',
            'response_time_ms' => 120,
            'depth' => 0,
        ]);

        $service = app(TechnologyDetectionService::class);

        $service->analyze($crawlRun);

        $this->assertDatabaseHas('detected_technologies', [
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'type' => 'cms',
            'name' => 'WordPress',
        ]);
    }

    public function test_it_replaces_existing_detections_for_a_crawl_run(): void
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

        $page = Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Example',
            'meta_description' => null,
            'html' => '<html><head><link href="/typo3conf/ext/sitepackage/app.css"></head></html>',
            'response_time_ms' => 120,
            'depth' => 0,
        ]);

        DetectedTechnology::query()->create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'type' => 'cms',
            'name' => 'OldDetection',
            'confidence' => 0.50,
            'evidence' => 'Old evidence.',
        ]);

        $service = app(TechnologyDetectionService::class);

        $service->analyze($crawlRun);

        $this->assertDatabaseMissing('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'name' => 'OldDetection',
        ]);

        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'type' => 'cms',
            'name' => 'TYPO3',
        ]);
    }
}