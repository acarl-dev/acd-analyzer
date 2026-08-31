<?php

namespace Tests\Unit\Services\Crawler\Download;

use App\Services\Crawler\Download\PageDownloader;
use App\Services\Crawler\Url\UrlNormalizer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PageDownloaderTest extends TestCase
{
    public function test_it_downloads_a_page_without_redirects(): void
    {
        Http::fake([
            'https://example.com' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com');

        $this->assertSame('https://example.com', $page->requestedUrl);
        $this->assertSame('https://example.com', $page->finalUrl);
        $this->assertSame(200, $page->statusCode);
        $this->assertSame(0, $page->redirectCount);
        $this->assertNull($page->redirectChain);
    }

    public function test_it_tracks_single_301_redirect(): void
    {
        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('http://example.com');

        $this->assertSame('http://example.com', $page->requestedUrl);
        $this->assertSame('https://example.com', $page->finalUrl);
        $this->assertSame(200, $page->statusCode);
        $this->assertSame(1, $page->redirectCount);
        $this->assertIsArray($page->redirectChain);
        $this->assertCount(1, $page->redirectChain);
        
        $hop = $page->redirectChain[0];
        $this->assertSame('http://example.com', $hop['from_url']);
        $this->assertSame(301, $hop['status_code']);
        $this->assertSame('https://example.com', $hop['location']);
        $this->assertSame('https://example.com', $hop['to_url']);
    }

    public function test_it_tracks_302_redirect(): void
    {
        Http::fake([
            'https://example.com/old' => Http::response('', 302, ['Location' => '/new']),
            'https://example.com/new' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/old');

        $this->assertSame('https://example.com/old', $page->requestedUrl);
        $this->assertSame('https://example.com/new', $page->finalUrl);
        $this->assertSame(1, $page->redirectCount);
        
        $hop = $page->redirectChain[0];
        $this->assertSame(302, $hop['status_code']);
    }

    public function test_it_tracks_307_redirect(): void
    {
        Http::fake([
            'https://example.com/old' => Http::response('', 307, ['Location' => 'https://example.com/new']),
            'https://example.com/new' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/old');

        $this->assertSame(1, $page->redirectCount);
        $this->assertSame(307, $page->redirectChain[0]['status_code']);
    }

    public function test_it_tracks_308_redirect(): void
    {
        Http::fake([
            'https://example.com/old' => Http::response('', 308, ['Location' => 'https://example.com/new']),
            'https://example.com/new' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/old');

        $this->assertSame(1, $page->redirectCount);
        $this->assertSame(308, $page->redirectChain[0]['status_code']);
    }

    public function test_it_tracks_multiple_redirect_hops(): void
    {
        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('', 301, ['Location' => 'https://www.example.com']),
            'https://www.example.com' => Http::response('', 302, ['Location' => 'https://www.example.com/de']),
            'https://www.example.com/de' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('http://example.com');

        $this->assertSame('http://example.com', $page->requestedUrl);
        $this->assertSame('https://www.example.com/de', $page->finalUrl);
        $this->assertSame(200, $page->statusCode);
        $this->assertSame(3, $page->redirectCount);
        $this->assertCount(3, $page->redirectChain);

        $this->assertSame(301, $page->redirectChain[0]['status_code']);
        $this->assertSame(301, $page->redirectChain[1]['status_code']);
        $this->assertSame(302, $page->redirectChain[2]['status_code']);
    }

    public function test_it_handles_relative_location_headers(): void
    {
        Http::fake([
            'https://example.com/page' => Http::response('', 301, ['Location' => '/contact']),
            'https://example.com/contact' => Http::response('<html>content</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/page');

        $this->assertSame('https://example.com/page', $page->requestedUrl);
        $this->assertSame('https://example.com/contact', $page->finalUrl);
        $this->assertSame(1, $page->redirectCount);

        $hop = $page->redirectChain[0];
        $this->assertSame('/contact', $hop['location']);
        $this->assertSame('https://example.com/contact', $hop['to_url']);
    }

    public function test_it_handles_redirect_to_external_domain(): void
    {
        Http::fake([
            'https://example.com/redirect' => Http::response('', 302, ['Location' => 'https://external.com/page']),
            'https://external.com/page' => Http::response('<html>external</html>', 200),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/redirect');

        $this->assertSame('https://example.com/redirect', $page->requestedUrl);
        $this->assertSame('https://external.com/page', $page->finalUrl);
        $this->assertSame(1, $page->redirectCount);

        $hop = $page->redirectChain[0];
        $this->assertSame('https://example.com/redirect', $hop['from_url']);
        $this->assertSame('https://external.com/page', $hop['to_url']);
    }

    public function test_it_stops_at_max_redirects(): void
    {
        // Create an infinite redirect loop
        Http::fake([
            'https://example.com/a' => Http::response('', 301, ['Location' => 'https://example.com/b']),
            'https://example.com/b' => Http::response('', 301, ['Location' => 'https://example.com/a']),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/a');

        $this->assertSame('https://example.com/a', $page->requestedUrl);
        $this->assertSame(10, $page->redirectCount);
        $this->assertCount(10, $page->redirectChain);
    }

    public function test_it_handles_redirect_without_location_header(): void
    {
        Http::fake([
            'https://example.com/broken' => Http::response('', 301),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/broken');

        $this->assertSame('https://example.com/broken', $page->requestedUrl);
        $this->assertSame('https://example.com/broken', $page->finalUrl);
        $this->assertSame(301, $page->statusCode);
        $this->assertSame(0, $page->redirectCount);
        $this->assertNull($page->redirectChain);
    }

    public function test_it_handles_redirect_with_invalid_location(): void
    {
        Http::fake([
            'https://example.com/page' => Http::response('', 301, ['Location' => 'mailto:test@example.com']),
        ]);

        $downloader = new PageDownloader(new UrlNormalizer());
        $page = $downloader->download('https://example.com/page');

        $this->assertSame('https://example.com/page', $page->requestedUrl);
        $this->assertSame('https://example.com/page', $page->finalUrl);
        $this->assertSame(301, $page->statusCode);
        $this->assertSame(0, $page->redirectCount);
    }
}