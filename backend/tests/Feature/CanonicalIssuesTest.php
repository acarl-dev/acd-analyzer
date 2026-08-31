<?php

namespace Tests\Feature;

use App\Services\CrawlAnalysisService;
use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanonicalIssuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_multiple_canonicals_in_crawl(): void
    {
        $html = <<<HTML
<html>
<head>
    <link rel="canonical" href="https://example.com/first">
    <link rel="canonical" href="https://example.com/second">
</head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            'https://example.com*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');
        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $this->assertDatabaseHas('page_issues', [
            'crawl_run_id' => $crawlRun->id,
            'code' => 'multiple_canonicals',
            'severity' => 'warning',
        ]);
    }

    public function test_it_detects_invalid_canonical_in_crawl(): void
    {
        $html = <<<HTML
<html>
<head>
    <link rel="canonical" href="mailto:test@example.com">
</head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            'https://example.com*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');
        
        $page = $crawlRun->pages()->first();

        // Canonical href should be stored but URL should be null
        $this->assertSame('mailto:test@example.com', $page->canonical_href);
        $this->assertNull($page->canonical_url);

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $this->assertDatabaseHas('page_issues', [
            'crawl_run_id' => $crawlRun->id,
            'code' => 'invalid_canonical',
            'severity' => 'error',
        ]);
    }

    public function test_it_detects_empty_canonical_in_crawl(): void
    {
        $html = <<<HTML
<html>
<head>
    <link rel="canonical" href="">
</head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            'https://example.com*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');
        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $this->assertDatabaseHas('page_issues', [
            'crawl_run_id' => $crawlRun->id,
            'code' => 'empty_canonical',
            'severity' => 'warning',
        ]);
    }

    public function test_it_detects_canonical_to_other_url_in_crawl(): void
    {
        $htmlDuplicate = <<<HTML
<html>
<head>
    <link rel="canonical" href="https://example.com/original">
</head>
<body>Duplicate content</body>
</html>
HTML;

        $htmlOriginal = <<<HTML
<html>
<head>
    <link rel="canonical" href="https://example.com/original">
</head>
<body>Original content</body>
</html>
HTML;

        Http::fake([
            'https://example.com' => Http::response($htmlOriginal, 200, [
                'Content-Type' => 'text/html',
            ]),
            'https://example.com/duplicate' => Http::response($htmlDuplicate, 200, [
                'Content-Type' => 'text/html',
            ]),
            'https://example.com/original' => Http::response($htmlOriginal, 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com/duplicate');
        
        $page = $crawlRun->pages()->where('final_url', 'https://example.com/duplicate')->first();

        $this->assertNotNull($page);
        $this->assertSame('https://example.com/original', $page->canonical_url);

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        $this->assertDatabaseHas('page_issues', [
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'code' => 'canonical_to_other_url',
            'severity' => 'info',
        ]);
    }

    public function test_it_does_not_detect_canonical_to_other_url_when_self_referencing(): void
    {
        $html = <<<HTML
<html>
<head>
    <link rel="canonical" href="https://example.com/">
</head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            'https://example.com*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');
        
        $page = $crawlRun->pages()->first();

        // Self-referencing canonical
        $this->assertSame('https://example.com', $page->canonical_url);
        $this->assertSame('https://example.com', $page->final_url);

        app(CrawlAnalysisService::class)->analyze($crawlRun);

        // Should not have canonical_to_other_url issue
        $this->assertDatabaseMissing('page_issues', [
            'crawl_run_id' => $crawlRun->id,
            'page_id' => $page->id,
            'code' => 'canonical_to_other_url',
        ]);
    }
}

