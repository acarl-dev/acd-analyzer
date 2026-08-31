<?php

namespace Tests\Feature;

use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanonicalPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_self_canonical(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="https://example.com/"></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'canonical_href' => 'https://example.com/',
            // UrlNormalizer removes trailing slash
            'canonical_url' => 'https://example.com',
            'canonical_count' => 1,
        ]);
    }

    public function test_stores_relative_canonical(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="/canonical-url"></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'canonical_href' => '/canonical-url',
            'canonical_url' => 'https://example.com/canonical-url',
            'canonical_count' => 1,
        ]);
    }

    public function test_stores_no_canonical(): void
    {
        $html = <<<HTML
<html>
<head><title>No Canonical</title></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'canonical_href' => null,
            'canonical_url' => null,
            'canonical_count' => 0,
        ]);
    }

    public function test_stores_multiple_canonicals(): void
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
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = $crawlRun->pages()->first();

        // First canonical should be stored
        $this->assertSame('https://example.com/first', $page->canonical_href);
        $this->assertSame('https://example.com/first', $page->canonical_url);
        $this->assertSame(2, $page->canonical_count);
    }

    public function test_stores_empty_canonical(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href=""></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'canonical_href' => '',
            'canonical_url' => null,
            'canonical_count' => 1,
        ]);
    }

    public function test_stores_canonical_with_fragment(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="https://example.com/#section"></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = $crawlRun->pages()->first();

        // Fragment should be in href but removed from url
        $this->assertSame('https://example.com/#section', $page->canonical_href);
        // UrlNormalizer removes trailing slash and fragment
        $this->assertSame('https://example.com', $page->canonical_url);
        $this->assertSame(1, $page->canonical_count);
    }

    public function test_stores_canonical_to_different_host(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="https://other.com/page"></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'canonical_href' => 'https://other.com/page',
            'canonical_url' => 'https://other.com/page',
            'canonical_count' => 1,
        ]);
    }

    public function test_page_issue_analyzer_detects_missing_canonical(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <title>Test with enough length for title</title>
    <meta name="description" content="This is a meta description with enough length to not trigger warnings about it being too short.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Main Heading</h1>
    <h2>Section Heading</h2>
    <p>This is test content with many words to ensure we pass the minimum word count check. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
    <a href="/internal1">Internal Link 1</a>
    <a href="/internal2">Internal Link 2</a>
    <a href="/internal3">Internal Link 3</a>
</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        // Only crawl the first page to avoid analyzing internal links
        $crawlRun = app(CrawlerService::class)->crawl('https://example.com',
            new \App\Services\Crawler\DTO\CrawlOptions(maxPages: 1)
        );

        $this->assertDatabaseHas('page_issues', [
            'code' => 'missing_canonical',
            'severity' => 'info',
        ]);
    }

    public function test_page_issue_analyzer_does_not_detect_missing_canonical_when_present(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <title>Test with enough length for title</title>
    <meta name="description" content="This is a meta description with enough length to not trigger warnings about it being too short.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="https://example.com/">
</head>
<body>
    <h1>Main Heading</h1>
    <h2>Section Heading</h2>
    <p>This is test content with many words to ensure we pass the minimum word count check. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
    <a href="/internal1">Internal Link 1</a>
    <a href="/internal2">Internal Link 2</a>
    <a href="/internal3">Internal Link 3</a>
</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        // Only crawl the first page to avoid analyzing internal links
        $crawlRun = app(CrawlerService::class)->crawl('https://example.com', 
            new \App\Services\Crawler\DTO\CrawlOptions(maxPages: 1)
        );

        $this->assertDatabaseMissing('page_issues', [
            'code' => 'missing_canonical',
        ]);
    }
}
