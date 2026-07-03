<?php

namespace Tests\Unit\Services\Crawler\Download;

use App\Services\Crawler\Download\PageContentFetcher;
use App\Services\Crawler\DTO\DownloadedPage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PageContentFetcherTest extends TestCase
{
    public function test_it_fetches_http_content_through_page_downloader(): void
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body>HTTP</body></html>', 200),
        ]);

        $page = app(PageContentFetcher::class)->fetchHttp('https://example.com');

        $this->assertInstanceOf(DownloadedPage::class, $page);
        $this->assertSame('https://example.com', $page->url);
        $this->assertSame(200, $page->statusCode);
        $this->assertSame('<html><body>HTTP</body></html>', $page->html);
    }

    public function test_it_fetches_rendered_content_through_browser_renderer(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => '<html><body><h1>Rendered</h1></body></html>',
            ]),
        ]);

        $page = app(PageContentFetcher::class)->fetchRendered('https://example.com');

        $this->assertInstanceOf(DownloadedPage::class, $page);
        $this->assertSame('https://example.com', $page->url);
        $this->assertSame(200, $page->statusCode);
        $this->assertSame('<html><body><h1>Rendered</h1></body></html>', $page->html);
    }

    public function test_it_returns_null_when_rendered_content_fails(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'error' => 'Rendering failed.',
            ], 500),
        ]);

        $page = app(PageContentFetcher::class)->fetchRendered('https://example.com');

        $this->assertNull($page);
    }
}