<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Crawler\CrawlerService;
use App\Models\CrawlError;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CrawlerErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_http_404_error_and_continues_crawl(): void
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body><h1>Home</h1><p>Content here to make it valid.</p><a href="/not-found">Link</a></body></html>', 200),
            'https://example.com/not-found' => Http::response('Not Found', 404),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('crawl_errors', [
            'crawl_run_id' => $crawlRun->id,
            'code' => 'http_4xx',
            'source' => 'http',
            'url' => 'https://example.com/not-found',
            'severity' => 'medium',
        ]);

        $this->assertEquals('completed', $crawlRun->status);
    }

    public function test_records_http_500_error_and_continues_crawl(): void
    {
        Http::fake([
            'https://example.com' => Http::response('<html><body><h1>Home</h1><p>Content here to make it valid.</p><a href="/error">Link</a></body></html>', 200),
            'https://example.com/error' => Http::response('Internal Server Error', 500),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('crawl_errors', [
            'crawl_run_id' => $crawlRun->id,
            'code' => 'http_5xx',
            'source' => 'http',
            'url' => 'https://example.com/error',
            'severity' => 'high',
        ]);

        $this->assertEquals('completed', $crawlRun->status);
    }

    public function test_fails_crawl_when_start_url_returns_404(): void
    {
        Http::fake([
            'https://example.com' => Http::response('Not Found', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertEquals('failed', $crawlRun->status);
        $this->assertStringContainsString('404', $crawlRun->error_message);
    }

    public function test_fails_crawl_when_start_url_returns_500(): void
    {
        Http::fake([
            'https://example.com' => Http::response('Internal Server Error', 500),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertEquals('failed', $crawlRun->status);
        $this->assertStringContainsString('500', $crawlRun->error_message);
    }

    public function test_records_redirect_limit_exceeded_error(): void
    {
        // Create redirect chain that exceeds limit
        $html = $this->getValidHtml('/page10');
        
        Http::fake([
            'https://example.com' => Http::response($html, 200),
            'https://example.com/page10' => Http::response('', 301, ['Location' => 'https://example.com/page11']),
            'https://example.com/page11' => Http::response('', 301, ['Location' => 'https://example.com/page12']),
            'https://example.com/page12' => Http::response('', 301, ['Location' => 'https://example.com/page13']),
            'https://example.com/page13' => Http::response('', 301, ['Location' => 'https://example.com/page14']),
            'https://example.com/page14' => Http::response('', 301, ['Location' => 'https://example.com/page15']),
            'https://example.com/page15' => Http::response('', 301, ['Location' => 'https://example.com/page16']),
            'https://example.com/page16' => Http::response('', 301, ['Location' => 'https://example.com/page17']),
            'https://example.com/page17' => Http::response('', 301, ['Location' => 'https://example.com/page18']),
            'https://example.com/page18' => Http::response('', 301, ['Location' => 'https://example.com/page19']),
            'https://example.com/page19' => Http::response('', 301, ['Location' => 'https://example.com/page20']),
            'https://example.com/page20' => Http::response('Final', 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $errors = CrawlError::where('crawl_run_id', $crawlRun->id)
            ->where('code', 'redirect_limit_exceeded')
            ->get();

        $this->assertGreaterThan(0, $errors->count());
        $this->assertEquals('completed', $crawlRun->status);
    }

    public function test_records_renderer_failure_and_falls_back_to_http(): void
    {
        config()->set('services.renderer.enabled', true);
        config()->set('services.renderer.url', 'http://renderer:3001');

        $jsHtml = '<html><body><div id="root">Loading...</div><script src="/app.js"></script></body></html>';
        
        Http::fake([
            'https://example.com' => Http::response($jsHtml, 200),
            'http://renderer:3001/render' => Http::response(null, 500),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('crawl_errors', [
            'crawl_run_id' => $crawlRun->id,
            'code' => 'renderer_failed',
            'source' => 'renderer',
            'url' => 'https://example.com',
        ]);

        // Should complete successfully with fallback to HTTP
        $this->assertEquals('completed', $crawlRun->status);
        $this->assertEquals(1, $crawlRun->pages()->count());
    }

    public function test_multiple_errors_recorded_for_different_pages(): void
    {
        Http::fake([
            'https://example.com' => Http::response($this->getMultipleErrorsHtml(), 200),
            'https://example.com/page1' => Http::response('Not Found', 404),
            'https://example.com/page2' => Http::response('Server Error', 500),
            'https://example.com/page3' => Http::response('OK', 200),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $errors = CrawlError::where('crawl_run_id', $crawlRun->id)->get();
        
        $this->assertGreaterThanOrEqual(2, $errors->count());
        $this->assertTrue($errors->contains('code', 'http_4xx'));
        $this->assertTrue($errors->contains('code', 'http_5xx'));
        $this->assertEquals('completed', $crawlRun->status);
    }

    public function test_error_context_contains_additional_info(): void
    {
        Http::fake([
            'https://example.com' => Http::response($this->getValidHtml('/error'), 200),
            'https://example.com/error' => Http::response('Not Found', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $error = CrawlError::where('crawl_run_id', $crawlRun->id)
            ->where('code', 'http_4xx')
            ->first();

        $this->assertNotNull($error);
        $this->assertIsArray($error->context);
        $this->assertArrayHasKey('status_code', $error->context);
        $this->assertEquals(404, $error->context['status_code']);
    }

    public function test_occurred_at_is_set_on_errors(): void
    {
        Http::fake([
            'https://example.com' => Http::response($this->getValidHtml('/error'), 200),
            'https://example.com/error' => Http::response('Not Found', 404),
        ]);

        $before = now()->subSecond();
        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');
        $after = now()->addSecond();

        $error = CrawlError::where('crawl_run_id', $crawlRun->id)->first();

        $this->assertNotNull($error->occurred_at);
        $this->assertTrue($error->occurred_at->between($before, $after));
    }

    private function getValidHtml(string $path): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page</title>
            <meta name="description" content="Test description">
        </head>
        <body>
            <h1>Test Heading</h1>
            <a href="$path">Link</a>
            <p>Some content here to make the body substantial.</p>
        </body>
        </html>
        HTML;
    }

    private function getMultipleErrorsHtml(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page</title>
        </head>
        <body>
            <h1>Test Heading</h1>
            <a href="/page1">Page 1</a>
            <a href="/page2">Page 2</a>
            <a href="/page3">Page 3</a>
            <p>Some content here to make the body substantial.</p>
        </body>
        </html>
        HTML;
    }
}
