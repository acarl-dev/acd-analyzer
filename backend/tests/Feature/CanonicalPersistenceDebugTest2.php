<?php

namespace Tests\Feature;

use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanonicalPersistenceDebugTest2 extends TestCase
{
    use RefreshDatabase;

    public function test_debug_canonical_with_page_issue_analyzer(): void
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
            'https://example.com*' => Http::response($html, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com',
            new \App\Services\Crawler\DTO\CrawlOptions(maxPages: 1)
        );

        $page = $crawlRun->pages()->first();

        // Debug: Check what's in the database
        $this->assertNotNull($page, 'Page should be created');
        dump([
            'canonical_href' => $page->canonical_href,
            'canonical_url' => $page->canonical_url,
            'canonical_count' => $page->canonical_count,
        ]);

        // Check issues
        $issues = $crawlRun->pages()->first()->issues;
        dump('Issues count: ' . $issues->count());
        foreach ($issues as $issue) {
            dump([
                'code' => $issue->code,
                'severity' => $issue->severity,
            ]);
        }

        $this->assertTrue(true); // Always pass, just for debugging
    }
}
