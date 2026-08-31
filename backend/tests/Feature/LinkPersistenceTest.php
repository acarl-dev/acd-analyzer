<?php

namespace Tests\Feature;

use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_raw_href_and_normalized_url(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <body>
                        <a href="/contact">Contact</a>
                        <a href="./about">About</a>
                        <a href="../team">Team</a>
                        <a href="https://external.com">External</a>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('links', [
            'href' => '/contact',
            'normalized_url' => 'https://example.com/contact',
            'is_internal' => true,
        ]);

        $this->assertDatabaseHas('links', [
            'href' => './about',
            'normalized_url' => 'https://example.com/about',
            'is_internal' => true,
        ]);

        $this->assertDatabaseHas('links', [
            'href' => '../team',
            'normalized_url' => 'https://example.com/team',
            'is_internal' => true,
        ]);

        $this->assertDatabaseHas('links', [
            'href' => 'https://external.com',
            'normalized_url' => 'https://external.com',
            'is_internal' => false,
        ]);
    }

    public function test_it_normalizes_links_with_fragments_and_query_parameters(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <body>
                        <a href="/page?foo=bar">With Query</a>
                        <a href="/page#section">With Fragment</a>
                        <a href="/page?foo=bar#section">Both</a>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('links', [
            'href' => '/page?foo=bar',
            'normalized_url' => 'https://example.com/page?foo=bar',
        ]);

        $this->assertDatabaseHas('links', [
            'href' => '/page#section',
            'normalized_url' => 'https://example.com/page',
        ]);

        $this->assertDatabaseHas('links', [
            'href' => '/page?foo=bar#section',
            'normalized_url' => 'https://example.com/page?foo=bar',
        ]);
    }

    public function test_it_handles_www_and_non_www_as_internal(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <body>
                        <a href="https://www.example.com/page">WWW Link</a>
                        <a href="https://example.com/page">Non-WWW Link</a>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('links', [
            'href' => 'https://www.example.com/page',
            'normalized_url' => 'https://www.example.com/page',
            'is_internal' => true,
        ]);

        $this->assertDatabaseHas('links', [
            'href' => 'https://example.com/page',
            'normalized_url' => 'https://example.com/page',
            'is_internal' => true,
        ]);
    }

    public function test_it_ignores_mailto_and_tel_links(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <body>
                        <a href="mailto:test@example.com">Email</a>
                        <a href="tel:+49123456789">Phone</a>
                        <a href="/contact">Contact</a>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseMissing('links', [
            'href' => 'mailto:test@example.com',
        ]);

        $this->assertDatabaseMissing('links', [
            'href' => 'tel:+49123456789',
        ]);

        $this->assertDatabaseHas('links', [
            'href' => '/contact',
            'normalized_url' => 'https://example.com/contact',
        ]);
    }

    public function test_it_handles_protocol_relative_urls(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <body>
                        <a href="//cdn.example.com/script.js">CDN Link</a>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('links', [
            'href' => '//cdn.example.com/script.js',
            'normalized_url' => 'https://cdn.example.com/script.js',
            'is_internal' => false,
        ]);
    }

    public function test_it_normalizes_hosts_to_lowercase(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <body>
                        <a href="https://Example.COM/Page">Mixed Case</a>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertDatabaseHas('links', [
            'href' => 'https://Example.COM/Page',
            'normalized_url' => 'https://example.com/Page',
            'is_internal' => true,
        ]);
    }
}