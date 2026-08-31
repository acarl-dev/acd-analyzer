<?php

namespace Tests\Feature;

use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RedirectHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_redirect_information_for_single_hop(): void
    {
        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response(
                '<html><head><title>Home</title></head><body><h1>Home</h1></body></html>',
                200
            ),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('http://example.com');

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'requested_url' => 'http://example.com',
            'final_url' => 'https://example.com',
            'url' => 'https://example.com',
            'status_code' => 200,
            'redirect_count' => 1,
        ]);

        $page = $crawlRun->pages()->first();
        $this->assertIsArray($page->redirect_chain);
        $this->assertCount(1, $page->redirect_chain);
        $this->assertSame(301, $page->redirect_chain[0]['status_code']);
    }

    public function test_it_persists_multiple_redirect_hops(): void
    {
        Http::fake([
            'http://example.com' => Http::response('', 301, ['Location' => 'https://example.com']),
            'https://example.com' => Http::response('', 301, ['Location' => 'https://www.example.com']),
            'https://www.example.com' => Http::response(
                '<html><head><title>Home</title></head><body><h1>Home</h1></body></html>',
                200
            ),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('http://example.com');

        $page = $crawlRun->pages()->first();
        
        $this->assertSame('http://example.com', $page->requested_url);
        $this->assertSame('https://www.example.com', $page->final_url);
        $this->assertSame(2, $page->redirect_count);
        $this->assertCount(2, $page->redirect_chain);
    }

    public function test_it_does_not_crawl_redirect_target_twice(): void
    {
        Http::fake(function ($request) {
            $url = (string) $request->url();
            
            if ($url === 'https://example.com') {
                return Http::response(
                    '<html>
                        <head><title>Home</title></head>
                        <body>
                            <h1>Home</h1>
                            <a href="/old-page">Old Page</a>
                            <a href="/contact">Contact</a>
                        </body>
                    </html>',
                    200
                );
            }
            
            if ($url === 'https://example.com/old-page') {
                return Http::response('', 301, ['Location' => '/contact']);
            }
            
            if ($url === 'https://example.com/contact') {
                return Http::response(
                    '<html><head><title>Contact</title></head><body><h1>Contact</h1></body></html>',
                    200
                );
            }
            
            return Http::response('', 404);
        });

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        // Should have 2 pages: home, old-page (redirects to contact)
        // The direct /contact link should be skipped since contact was already visited via redirect
        $this->assertSame(2, $crawlRun->pages_crawled);
        
        // Contact should only appear once (via redirect from old-page)
        $contactPages = $crawlRun->pages()
            ->where('final_url', 'https://example.com/contact')
            ->count();
        
        $this->assertSame(1, $contactPages);
        
        // Verify the contact page was reached via redirect
        $contactPage = $crawlRun->pages()
            ->where('final_url', 'https://example.com/contact')
            ->first();
        
        $this->assertSame('https://example.com/old-page', $contactPage->requested_url);
        $this->assertSame('https://example.com/contact', $contactPage->final_url);
        $this->assertSame(1, $contactPage->redirect_count);
    }

    public function test_it_handles_redirect_to_external_domain(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <head><title>Home</title></head>
                    <body>
                        <h1>Home</h1>
                        <a href="/external-redirect">External</a>
                    </body>
                </html>',
                200
            ),
            'https://example.com/external-redirect' => Http::response('', 302, ['Location' => 'https://external.com']),
            'https://external.com' => Http::response('<html><head><title>External</title></head></html>', 200),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        // Should have 2 pages: home and external-redirect (which redirects away)
        // External domain should not be crawled
        $this->assertSame(2, $crawlRun->pages_crawled);

        $redirectPage = $crawlRun->pages()
            ->where('requested_url', 'https://example.com/external-redirect')
            ->first();

        $this->assertNotNull($redirectPage);
        $this->assertSame('https://external.com', $redirectPage->final_url);
        $this->assertSame(1, $redirectPage->redirect_count);
    }

    public function test_pages_without_redirects_have_matching_requested_and_final_urls(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head><title>Home</title></head><body><h1>Home</h1></body></html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $page = $crawlRun->pages()->first();

        $this->assertSame('https://example.com', $page->requested_url);
        $this->assertSame('https://example.com', $page->final_url);
        $this->assertSame('https://example.com', $page->url);
        $this->assertSame(0, $page->redirect_count);
        $this->assertNull($page->redirect_chain);
    }
}