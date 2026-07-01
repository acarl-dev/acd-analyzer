<?php

namespace Tests\Feature;

use App\Models\CrawlError;
use App\Models\CrawlRun;
use App\Models\Heading;
use App\Models\Image;
use App\Models\Link;
use App\Models\Page;
use App\Models\Website;
use App\Services\CrawlResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\PageIssue;

class CrawlResultsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_results_with_page_issue_analyzer_issues_and_summary_counts(): void
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
            'url' => 'https://example.com/start',
            'status_code' => 200,
            'title' => 'A useful page title',
            'meta_description' => 'This is a useful meta description that explains the page content clearly.',
            'html' => str_repeat('x', 500001),
        ]);

        Heading::forceCreate([
            'page_id' => $page->id,
            'level' => 1,
            'text' => 'Main heading',
        ]);

        Image::forceCreate([
            'page_id' => $page->id,
            'src' => 'https://example.com/image-1.jpg',
            'alt' => null,
        ]);

        Image::forceCreate([
            'page_id' => $page->id,
            'src' => 'https://example.com/image-2.jpg',
            'alt' => '',
        ]);

        Link::forceCreate([
            'page_id' => $page->id,
            'href' => 'https://external.example.org',
            'text' => 'External link',
            'is_internal' => false,
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'url' => $page->url,
            'code' => 'images_without_alt',
            'severity' => 'warning',
            'message' => '2 Bilder haben kein alt-Attribut.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'url' => $page->url,
            'code' => 'high_missing_alt_ratio',
            'severity' => 'warning',
            'message' => 'Ein hoher Anteil der Bilder hat kein alt-Attribut.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'url' => $page->url,
            'code' => 'few_internal_links',
            'severity' => 'warning',
            'message' => 'Die Seite hat sehr wenige interne Links.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'url' => $page->url,
            'code' => 'large_html_size',
            'severity' => 'info',
            'message' => 'Die gespeicherte HTML-Datei ist ungewöhnlich groß.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        $results = app(CrawlResultsService::class)->buildForCrawlRun($crawlRun);

        $this->assertSame($crawlRun->id, $results['crawlRunId']);
        $this->assertSame($website->id, $results['websiteId']);
        $this->assertSame('https://example.com', $results['siteUrl']);

        $this->assertCount(1, $results['pages']);

        $pageResult = $results['pages'][0];
        $issueCodes = array_column($pageResult['issues'], 'code');

        $this->assertContains('images_without_alt', $issueCodes);
        $this->assertContains('high_missing_alt_ratio', $issueCodes);
        $this->assertContains('few_internal_links', $issueCodes);
        $this->assertContains('large_html_size', $issueCodes);

        $this->assertSame(1, $results['summary']['totalPages']);
        $this->assertSame(1, $results['summary']['successfulPages']);
        $this->assertSame(0, $results['summary']['failedPages']);
        $this->assertSame(1, $results['summary']['pagesWithIssues']);
        $this->assertSame(4, $results['summary']['totalIssues']);
        $this->assertSame(0, $results['summary']['errors']);
        $this->assertSame(3, $results['summary']['warnings']);
        $this->assertSame(1, $results['summary']['infos']);
    }

    public function test_it_maps_crawl_errors_into_results_and_summary_counts(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 0,
        ]);

        $crawlError = CrawlError::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/broken',
            'message' => 'Connection timeout',
            'depth' => 1,
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'crawl_error_id' => $crawlError->id,
            'url' => $crawlError->url,
            'code' => 'crawl_error',
            'severity' => 'error',
            'message' => 'Die Seite konnte nicht gecrawlt werden: Connection timeout',
            'context' => [
                'message' => 'Connection timeout',
            ],
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        $results = app(CrawlResultsService::class)->buildForCrawlRun($crawlRun);

        $this->assertCount(1, $results['pages']);

        $errorResult = $results['pages'][0];

        $this->assertTrue($errorResult['hasCrawlError']);
        $this->assertSame(1, $errorResult['depth']);
        $this->assertSame('Connection timeout', $errorResult['crawlError']);
        $this->assertSame('crawl_error', $errorResult['issues'][0]['code']);
        $this->assertSame('error', $errorResult['issues'][0]['severity']);

        $this->assertSame(1, $results['summary']['totalPages']);
        $this->assertSame(0, $results['summary']['successfulPages']);
        $this->assertSame(1, $results['summary']['failedPages']);
        $this->assertSame(1, $results['summary']['pagesWithIssues']);
        $this->assertSame(1, $results['summary']['totalIssues']);
        $this->assertSame(1, $results['summary']['errors']);
        $this->assertSame(0, $results['summary']['warnings']);
        $this->assertSame(0, $results['summary']['infos']);
    }

    public function test_it_returns_persisted_issues_sorted_by_severity(): void
    {
        $website = Website::create([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
            'pages_crawled' => 1,
        ]);

        $page = Page::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Example',
            'meta_description' => 'Example meta description',
            'html' => '<html><body><h1>Example</h1></body></html>',
        ]);

        PageIssue::create([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'crawl_error_id' => null,
            'url' => $page->url,
            'code' => 'info_issue',
            'severity' => 'info',
            'message' => 'Info issue',
        ]);

        PageIssue::create([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'crawl_error_id' => null,
            'url' => $page->url,
            'code' => 'error_issue',
            'severity' => 'error',
            'message' => 'Error issue',
        ]);

        PageIssue::create([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'crawl_error_id' => null,
            'url' => $page->url,
            'code' => 'warning_issue',
            'severity' => 'warning',
            'message' => 'Warning issue',
        ]);

        $result = app(CrawlResultsService::class)->buildForCrawlRun($crawlRun);

        $issues = $result['pages'][0]['issues'];

        $this->assertSame([
            'error',
            'warning',
            'info',
        ], array_column($issues, 'severity'));

        $this->assertSame([
            'error_issue',
            'warning_issue',
            'info_issue',
        ], array_column($issues, 'code'));
    }
}