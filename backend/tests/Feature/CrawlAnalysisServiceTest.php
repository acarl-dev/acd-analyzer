<?php

namespace Tests\Feature;

use App\Models\CrawlError;
use App\Models\CrawlRun;
use App\Models\Heading;
use App\Models\Image;
use App\Models\Link;
use App\Models\Page;
use App\Models\PageIssue;
use App\Models\Website;
use App\Services\CrawlAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_page_issues_for_a_crawl_run(): void
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
            'title' => 'Hi',
            'meta_description' => 'Too short',
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

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $issues = PageIssue::query()
            ->where('crawl_run_id', $crawlRun->id)
            ->orderBy('id')
            ->get();

        $issueCodes = $issues
            ->pluck('code')
            ->all();

        $this->assertContains('title_too_short', $issueCodes);
        $this->assertContains('meta_description_too_short', $issueCodes);
        $this->assertContains('images_without_alt', $issueCodes);
        $this->assertContains('high_missing_alt_ratio', $issueCodes);
        $this->assertContains('few_internal_links', $issueCodes);
        $this->assertContains('large_html_size', $issueCodes);

        $this->assertSame(
            'warning',
            $issues->firstWhere('code', 'few_internal_links')?->severity
        );

        $this->assertSame(
            'info',
            $issues->firstWhere('code', 'large_html_size')?->severity
        );

        $this->assertTrue(
            $issues->every(fn (PageIssue $issue) => $issue->page_id === $page->id)
        );

        $this->assertTrue(
            $issues->every(fn (PageIssue $issue) => $issue->analyzer_version === 'page_issue_analyzer:v1')
        );
    }

    public function test_it_persists_slow_response_time_issue_for_a_crawl_run(): void
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
            'url' => 'https://example.com/slow',
            'status_code' => 200,
            'title' => 'A useful page title',
            'meta_description' => 'This is a useful meta description for the page content.',
            'html' => '<html lang="de"><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="https://example.com/slow"></head><body>' . str_repeat('word ', 120) . '</body></html>',
            'response_time_ms' => 2500,
        ]);

        Heading::forceCreate([
            'page_id' => $page->id,
            'level' => 1,
            'text' => 'Main heading',
        ]);

        Heading::forceCreate([
            'page_id' => $page->id,
            'level' => 2,
            'text' => 'Section heading',
        ]);

        Link::forceCreate([
            'page_id' => $page->id,
            'href' => 'https://example.com/about',
            'text' => 'About',
            'is_internal' => true,
        ]);

        Link::forceCreate([
            'page_id' => $page->id,
            'href' => 'https://example.com/contact',
            'text' => 'Contact',
            'is_internal' => true,
        ]);

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $this->assertDatabaseHas('page_issues', [
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'url' => $page->url,
            'code' => 'slow_response_time',
            'severity' => 'warning',
            'message' => 'Die Seite hat eine langsame Server-Antwortzeit.',
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);
    }

    public function test_it_persists_crawl_error_issues_for_a_crawl_run(): void
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
        ]);

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $issue = PageIssue::query()
            ->where('crawl_run_id', $crawlRun->id)
            ->first();

        $this->assertNotNull($issue);
        $this->assertSame($crawlError->id, $issue->crawl_error_id);
        $this->assertSame('https://example.com/broken', $issue->url);
        $this->assertSame('crawl_error', $issue->code);
        $this->assertSame('error', $issue->severity);
        $this->assertSame('page_issue_analyzer:v1', $issue->analyzer_version);
    }

    public function test_it_replaces_existing_issues_when_reanalyzing_a_crawl_run(): void
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
            'title' => null,
            'meta_description' => null,
            'html' => '',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'url' => $page->url,
            'code' => 'old_issue',
            'severity' => 'warning',
            'message' => 'Old issue that should be deleted.',
            'context' => null,
            'analyzer_version' => 'old',
        ]);

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $issueCodes = PageIssue::query()
            ->where('crawl_run_id', $crawlRun->id)
            ->pluck('code')
            ->all();

        $this->assertNotContains('old_issue', $issueCodes);
        $this->assertContains('missing_title', $issueCodes);
        $this->assertContains('missing_meta_description', $issueCodes);
        $this->assertContains('missing_h1', $issueCodes);
    }
}