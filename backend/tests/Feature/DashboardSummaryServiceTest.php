<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\PageIssue;
use App\Models\Website;
use App\Services\DashboardSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\DetectedTechnology;
use App\Models\Page;

class DashboardSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_dashboard_summary_from_persisted_issues(): void
    {
        $websiteWithIssues = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $websiteWithoutIssues = Website::forceCreate([
            'url' => 'https://clean.example.com',
            'host' => 'clean.example.com',
        ]);

        $crawlRunWithIssues = CrawlRun::forceCreate([
            'website_id' => $websiteWithIssues->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        CrawlRun::forceCreate([
            'website_id' => $websiteWithoutIssues->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRunWithIssues->id,
            'url' => 'https://example.com',
            'code' => 'images_without_alt',
            'severity' => 'warning',
            'message' => '1 Bild(er) haben keinen Alt-Text.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRunWithIssues->id,
            'url' => 'https://example.com',
            'code' => 'images_without_alt',
            'severity' => 'warning',
            'message' => '1 Bild(er) haben keinen Alt-Text.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRunWithIssues->id,
            'url' => 'https://example.com',
            'code' => 'large_html_size',
            'severity' => 'info',
            'message' => 'Die gespeicherte HTML-Größe ist ungewöhnlich groß.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        $summary = app(DashboardSummaryService::class)->build();

        $this->assertSame(2, $summary['totalWebsites']);
        $this->assertSame(2, $summary['totalCrawlRuns']);
        $this->assertSame(1, $summary['websitesWithIssues']);
        $this->assertSame(3, $summary['totalIssues']);

        $this->assertSame(0, $summary['issuesBySeverity']['errors']);
        $this->assertSame(2, $summary['issuesBySeverity']['warnings']);
        $this->assertSame(1, $summary['issuesBySeverity']['infos']);

        $this->assertSame('images_without_alt', $summary['topIssues'][0]['code']);
        $this->assertSame('warning', $summary['topIssues'][0]['severity']);
        $this->assertSame(2, $summary['topIssues'][0]['count']);

        $this->assertSame('large_html_size', $summary['topIssues'][1]['code']);
        $this->assertSame('info', $summary['topIssues'][1]['severity']);
        $this->assertSame(1, $summary['topIssues'][1]['count']);
    }

    public function test_it_returns_top_detected_technologies(): void
    {
        $website = Website::create([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $firstCrawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'completed',
            'max_pages' => 10,
            'max_depth' => 1,
        ]);

        $secondCrawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'completed',
            'max_pages' => 10,
            'max_depth' => 1,
        ]);

        $firstPage = Page::create([
            'website_id' => $website->id,
            'crawl_run_id' => $firstCrawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Example',
            'html' => '<html></html>',
        ]);

        $secondPage = Page::create([
            'website_id' => $website->id,
            'crawl_run_id' => $firstCrawlRun->id,
            'url' => 'https://example.com/about',
            'status_code' => 200,
            'title' => 'About',
            'html' => '<html></html>',
        ]);

        $thirdPage = Page::create([
            'website_id' => $website->id,
            'crawl_run_id' => $secondCrawlRun->id,
            'url' => 'https://example.com/contact',
            'status_code' => 200,
            'title' => 'Contact',
            'html' => '<html></html>',
        ]);

        DetectedTechnology::create([
            'website_id' => $website->id,
            'crawl_run_id' => $firstCrawlRun->id,
            'page_id' => $firstPage->id,
            'type' => 'frontend',
            'name' => 'Next.js',
            'confidence' => 0.9,
            'evidence' => 'next-data script found',
        ]);

        DetectedTechnology::create([
            'website_id' => $website->id,
            'crawl_run_id' => $firstCrawlRun->id,
            'page_id' => $secondPage->id,
            'type' => 'frontend',
            'name' => 'Next.js',
            'confidence' => 0.8,
            'evidence' => 'next-data script found',
        ]);

        DetectedTechnology::create([
            'website_id' => $website->id,
            'crawl_run_id' => $secondCrawlRun->id,
            'page_id' => $thirdPage->id,
            'type' => 'frontend',
            'name' => 'React',
            'confidence' => 0.7,
            'evidence' => 'next-data script found',
        ]);

        $summary = app(DashboardSummaryService::class)->build();

        $this->assertSame([
            [
                'type' => 'frontend',
                'name' => 'Next.js',
                'confidence' => 0.9,
                'count' => 1,
            ],
            [
                'type' => 'frontend',
                'name' => 'React',
                'confidence' => 0.7,
                'count' => 1,
            ],
        ], $summary['topTechnologies']);
    }
}