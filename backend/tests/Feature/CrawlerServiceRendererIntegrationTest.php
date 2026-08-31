<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrawlerServiceRendererIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_http_for_page_with_sufficient_content(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <head><title>Good Page</title></head>
                    <body>
                        <h1>Welcome</h1>
                        <nav>
                            <a href="/about">About</a>
                            <a href="/services">Services</a>
                            <a href="/contact">Contact</a>
                        </nav>
                        <p>This is a good page with sufficient content.</p>
                        <p>It has multiple paragraphs and links.</p>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = Page::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($page);
        $this->assertSame('http', $page->fetch_method);
        $this->assertNull($page->renderer_reason);
    }

    public function test_it_uses_renderer_for_page_with_empty_body(): void
    {
        config()->set('services.renderer.enabled', true);
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>Empty</title></head><body></body></html>',
                200
            ),
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => '<html><head><title>Rendered</title></head><body><h1>Rendered Content</h1><a href="/link">Link</a></body></html>',
            ]),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = Page::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($page);
        $this->assertSame('renderer', $page->fetch_method);
        $this->assertSame('empty_content', $page->renderer_reason);
        $this->assertSame('Rendered', $page->title);
    }

    public function test_it_uses_renderer_for_js_heavy_page(): void
    {
        config()->set('services.renderer.enabled', true);
        config()->set('services.renderer.url', 'http://renderer:3001');

        $jsHeavyHtml = '<html><head><title>SPA</title></head><body>';
        $jsHeavyHtml .= '<div class="app">Loading application please wait for initialization to complete...</div>';
        $jsHeavyHtml .= '<script src="/bundle1.js"></script>';
        $jsHeavyHtml .= '<script src="/bundle2.js"></script>';
        $jsHeavyHtml .= '<script src="/bundle3.js"></script>';
        $jsHeavyHtml .= '</body></html>';

        Http::fake([
            'https://example.com' => Http::response($jsHeavyHtml, 200),
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => '<html><head><title>SPA Rendered</title></head><body><div class="app"><h1>App Loaded</h1><a href="/page1">Page 1</a></div></body></html>',
            ]),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = Page::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($page);
        $this->assertSame('renderer', $page->fetch_method);
        $this->assertStringContainsString('_heavy', $page->renderer_reason ?? '');
        $this->assertSame('SPA Rendered', $page->title);
    }

    public function test_it_falls_back_to_http_when_renderer_fails(): void
    {
        config()->set('services.renderer.enabled', true);
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>Original</title></head><body><div id="root"></div></body></html>',
                200
            ),
            'http://renderer:3001/render' => Http::response([
                'error' => 'Rendering failed',
            ], 500),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = Page::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($page);
        // Should fall back to HTTP content with renderer reason recorded
        $this->assertSame('http', $page->fetch_method);
        $this->assertNotNull($page->renderer_reason);
        $this->assertSame('Original', $page->title);
    }

    public function test_it_respects_renderer_disabled_config(): void
    {
        config()->set('services.renderer.enabled', false);

        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>Empty</title></head><body></body></html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = Page::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($page);
        $this->assertSame('http', $page->fetch_method);
        $this->assertNull($page->renderer_reason);
    }

    public function test_it_respects_max_rendered_pages_limit(): void
    {
        config()->set('services.renderer.enabled', true);
        config()->set('services.renderer.max_per_crawl', 2);
        config()->set('services.renderer.url', 'http://renderer:3001');

        $emptyHtml = '<html><head><title>Empty</title></head><body></body></html>';
        $renderedHtml = '<html><head><title>Rendered</title></head><body><h1>Content</h1></body></html>';

        Http::fake([
            'https://example.com' => Http::response(
                $emptyHtml . '<body><a href="/page1">Page 1</a><a href="/page2">Page 2</a><a href="/page3">Page 3</a></body></html>',
                200
            ),
            'https://example.com/page1' => Http::response($emptyHtml, 200),
            'https://example.com/page2' => Http::response($emptyHtml, 200),
            'https://example.com/page3' => Http::response($emptyHtml, 200),
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => $renderedHtml,
            ]),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com', 
            new \App\Services\Crawler\DTO\CrawlOptions(maxPages: 10, maxDepth: 2)
        );

        $renderedPages = Page::where('crawl_run_id', $crawlRun->id)
            ->where('fetch_method', 'renderer')
            ->count();

        // Only first 2 pages should be rendered due to limit
        $this->assertLessThanOrEqual(2, $renderedPages);
    }

    public function test_it_uses_http_for_ssr_page_with_sufficient_content(): void
    {
        $ssrHtml = '<html><head><title>SSR Page</title><script>__NEXT_DATA__={}</script></head><body>';
        $ssrHtml .= '<div id="__next">';
        $ssrHtml .= '<h1>Welcome</h1><h2>About</h2>';
        $ssrHtml .= '<nav><a href="/">Home</a><a href="/about">About</a><a href="/services">Services</a></nav>';
        $ssrHtml .= str_repeat('<p>Server-side rendered content.</p>', 20);
        $ssrHtml .= '</div>';
        $ssrHtml .= '<script src="/_next/static/main.js"></script>';
        $ssrHtml .= '</body></html>';

        Http::fake([
            'https://example.com' => Http::response($ssrHtml, 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = Page::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($page);
        // SSR page with good content should use HTTP
        $this->assertSame('http', $page->fetch_method);
        $this->assertNull($page->renderer_reason);
    }
}
