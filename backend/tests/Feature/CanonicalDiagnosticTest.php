<?php

namespace Tests\Feature;

use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanonicalDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_canonical_extraction(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="https://example.com/"></head>
<body>Test</body>
</html>
HTML;

        \Log::info('HTML to fake:', ['html' => $html]);

        // Use Http::fake with ALL requests to prevent real HTTP calls
        Http::preventStrayRequests();
        
        Http::fake([
            '*' => function ($request) use ($html) {
                \Log::info('HTTP request matched', [
                    'url' => $request->url(),
                    'returning_html' => $html,
                ]);
                return Http::response($html, 200);
            },
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = $crawlRun->pages()->first();
        
        \Log::info('Stored page data:', [
            'canonical_href' => $page->canonical_href,
            'canonical_url' => $page->canonical_url,
            'canonical_count' => $page->canonical_count,
            'html_snippet' => substr($page->html, 0, 200),
        ]);

        $this->assertNotNull($page);
        $this->assertSame('https://example.com/', $page->canonical_href);
        $this->assertSame('https://example.com', $page->canonical_url);
        $this->assertSame(1, $page->canonical_count);
    }
}
