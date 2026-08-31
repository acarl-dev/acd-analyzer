<?php

namespace Tests\Unit\Services\Crawler\Rendering;

use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Crawler\Rendering\RenderDecisionService;
use Tests\TestCase;

class RenderDecisionServiceTest extends TestCase
{
    private RenderDecisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RenderDecisionService();
    }

    public function test_should_not_render_when_http_fetch_failed(): void
    {
        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 404,
            html: '<html><body>Not Found</body></html>',
            responseTimeMs: 100,
        );

        $this->assertFalse($this->service->shouldRender($page));
        $this->assertNull($this->service->getReason($page));
    }

    public function test_should_not_render_when_html_is_empty(): void
    {
        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: '',
            responseTimeMs: 100,
        );

        $this->assertFalse($this->service->shouldRender($page));
    }

    public function test_should_render_when_body_is_empty(): void
    {
        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: '<html><head><title>Test</title></head><body></body></html>',
            responseTimeMs: 100,
        );

        $this->assertTrue($this->service->shouldRender($page));
        $this->assertSame('empty_content', $this->service->getReason($page));
    }

    public function test_should_render_when_body_has_minimal_content(): void
    {
        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: '<html><head><title>Test</title></head><body><script>console.log("hi")</script>Loading...</body></html>',
            responseTimeMs: 100,
        );

        $this->assertTrue($this->service->shouldRender($page));
        $this->assertSame('empty_content', $this->service->getReason($page));
    }

    public function test_should_render_when_has_app_root_with_insufficient_content(): void
    {
        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: '<html><head><title>App</title></head><body><div id="root">Loading your application, please wait just a moment while we initialize...</div><script src="/app.js"></script></body></html>',
            responseTimeMs: 100,
        );

        $this->assertTrue($this->service->shouldRender($page));
        $this->assertSame('insufficient_html', $this->service->getReason($page));
    }

    public function test_should_render_when_has_next_js_root_with_insufficient_content(): void
    {
        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: '<html><head><title>Next App</title></head><body><div id="__next">Loading Next.js application please wait for initialization...</div><script src="/_next/static/app.js"></script></body></html>',
            responseTimeMs: 100,
        );

        $this->assertTrue($this->service->shouldRender($page));
        $this->assertSame('insufficient_html', $this->service->getReason($page));
    }

    public function test_should_not_render_when_has_app_root_with_sufficient_content(): void
    {
        $html = '<html><head><title>App</title></head><body><div id="root">';
        $html .= '<h1>Welcome</h1><h2>About</h2>';
        $html .= '<a href="/link1">Link 1</a><a href="/link2">Link 2</a><a href="/link3">Link 3</a>';
        $html .= '<a href="/link4">Link 4</a><a href="/link5">Link 5</a><a href="/link6">Link 6</a>';
        $html .= str_repeat('<p>This is a paragraph with content.</p>', 20);
        $html .= '</div><script src="/app.js"></script></body></html>';

        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: $html,
            responseTimeMs: 100,
        );

        $this->assertFalse($this->service->shouldRender($page));
    }

    public function test_should_render_when_js_heavy_with_minimal_content(): void
    {
        $html = '<html><head><title>App</title></head><body>';
        $html .= '<script src="/bundle1.js"></script>';
        $html .= '<script src="/bundle2.js"></script>';
        $html .= '<script src="/bundle3.js"></script>';
        $html .= '<div>Loading application please wait while we initialize the content...</div>';
        $html .= '</body></html>';

        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: $html,
            responseTimeMs: 100,
        );

        $this->assertTrue($this->service->shouldRender($page));
        $this->assertSame('js_heavy', $this->service->getReason($page));
    }

    public function test_should_render_when_javascript_required_message(): void
    {
        $html = '<html><head><title>App</title></head><body>';
        $html .= '<noscript>Please enable JavaScript to view this site and access all features properly.</noscript>';
        $html .= '<div>Application content loading, JavaScript required for full functionality</div>';
        $html .= '<script src="/bundle.js"></script>';
        $html .= '<script src="/main.js"></script>';
        $html .= '</body></html>';

        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: $html,
            responseTimeMs: 100,
        );

        $this->assertTrue($this->service->shouldRender($page));
        $this->assertSame('js_heavy', $this->service->getReason($page));
    }

    public function test_should_not_render_ssr_page_with_sufficient_content(): void
    {
        $html = '<html><head><title>SSR App</title><script>__NEXT_DATA__={}</script></head><body>';
        $html .= '<div id="__next">';
        $html .= '<h1>Welcome to our site</h1><h2>About Us</h2><h3>Services</h3>';
        $html .= '<nav>';
        $html .= '<a href="/">Home</a><a href="/about">About</a><a href="/services">Services</a>';
        $html .= '<a href="/contact">Contact</a><a href="/blog">Blog</a><a href="/products">Products</a>';
        $html .= '</nav>';
        $html .= str_repeat('<p>This is server-side rendered content.</p>', 30);
        $html .= '</div>';
        $html .= '<script src="/_next/static/chunks/main.js"></script>';
        $html .= '</body></html>';

        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: $html,
            responseTimeMs: 100,
        );

        // SSR page with good content should not need rendering
        $this->assertFalse($this->service->shouldRender($page));
    }

    public function test_should_not_render_successful_http_page_with_good_content(): void
    {
        $html = '<html><head><title>Good Page</title></head><body>';
        $html .= '<h1>Welcome</h1>';
        $html .= '<nav><a href="/">Home</a><a href="/about">About</a><a href="/contact">Contact</a></nav>';
        $html .= str_repeat('<p>This is a paragraph with meaningful content about our company.</p>', 25);
        $html .= '</body></html>';

        $page = new DownloadedPage(
            requestedUrl: 'https://example.com',
            finalUrl: 'https://example.com',
            statusCode: 200,
            html: $html,
            responseTimeMs: 100,
        );

        $this->assertFalse($this->service->shouldRender($page));
    }
}
