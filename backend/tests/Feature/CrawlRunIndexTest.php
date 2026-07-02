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
            'meta_description' => 'Example meta description',
            'html' => '<html><body><h1>Example</h1></body></html>',
        ]);

        $crawlError = CrawlError::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/broken',
            'message' => 'Connection timeout',
            'depth' => 1,
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'crawl_error_id' => null,
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
            'crawl_error_id' => null,
            'url' => $page->url,
            'code' => 'missing_canonical',
            'severity' => 'info',
            'message' => 'Die Seite enthält keinen Canonical-Link.',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

        PageIssue::forceCreate([
            'crawl_run_id' => $crawlRun->id,
            'page_id' => null,
            'crawl_error_id' => $crawlError->id,
            'url' => $crawlError->url,
            'code' => 'crawl_error',
            'severity' => 'error',
            'message' => 'Die Seite konnte nicht gecrawlt werden: Connection timeout',
            'context' => null,
            'analyzer_version' => 'page_issue_analyzer:v1',
        ]);

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
}