<?php

namespace Tests\Feature;

use App\Models\CrawlError;
use App\Models\CrawlRun;
use App\Models\Page;
use App\Models\PageIssue;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlRunIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_includes_health_score_in_crawl_run_list(): void
    {
        $website = $this->createWebsite();
        $crawlRun = $this->createCrawlRun($website);
        $page = $this->createPage($website, $crawlRun);
        $crawlError = $this->createCrawlError($crawlRun);

        $this->createIssueForPage(
            crawlRun: $crawlRun,
            page: $page,
            code: 'few_internal_links',
            severity: 'warning',
            message: 'Die Seite hat sehr wenige interne Links.',
        );

        $this->createIssueForPage(
            crawlRun: $crawlRun,
            page: $page,
            code: 'missing_canonical',
            severity: 'info',
            message: 'Die Seite enthält keinen Canonical-Link.',
        );

        $this->createIssueForCrawlError(
            crawlRun: $crawlRun,
            crawlError: $crawlError,
            code: 'crawl_error',
            severity: 'error',
            message: 'Die Seite konnte nicht gecrawlt werden: Connection timeout',
        );

        $response = $this->getJson('/api/crawl-runs');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $crawlRun->id)
            ->assertJsonPath('data.0.healthScore', 79)
            ->assertJsonPath('data.0.issueSummary.total', 3)
            ->assertJsonPath('data.0.issueSummary.errors', 1)
            ->assertJsonPath('data.0.issueSummary.warnings', 1)
            ->assertJsonPath('data.0.issueSummary.infos', 1);
    }

    private function createWebsite(): Website
    {
        return Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);
    }

    private function createCrawlRun(Website $website): CrawlRun
    {
        return CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);
    }

    private function createPage(Website $website, CrawlRun $crawlRun): Page
    {
        return Page::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => 'Example',
            'meta_description' => 'Example meta description',
            'html' => '<html><body><h1>Example</h1></body></html>',
        ]);
    }

    private function createCrawlError(CrawlRun $crawlRun): CrawlError
    {
        return CrawlError::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/broken',
            'message' => 'Connection timeout',
            'depth' => 1,
        ]);
    }

    private function createIssueForPage(
        CrawlRun $crawlRun,
        Page $page,
        string $code,
        string $severity,
        string $message,
    ): PageIssue {
        return PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'crawl_error_id' => null,
            'url' => $page->url,
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);
    }

    private function createIssueForCrawlError(
        CrawlRun $crawlRun,
        CrawlError $crawlError,
        string $code,
        string $severity,
        string $message,
    ): PageIssue {
        return PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => null,
            'crawl_error_id' => $crawlError->id,
            'url' => $crawlError->url,
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);
    }
}