<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\PageIssue;
use App\Models\Website;
use App\Services\DashboardSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}