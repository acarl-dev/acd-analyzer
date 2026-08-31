<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Website;
use App\Services\Crawler\CrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrawlerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_crawls_an_internal_link_from_the_start_page(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <head>
                        <title>Startseite</title>
                        <meta name="description" content="Das ist die Startseite.">
                        <link rel="stylesheet" href="/wp-content/themes/theme/style.css">
                    </head>
                    <body>
                        <h1>Startseite</h1>
                        <a href="/kontakt">Kontakt</a>
                    </body>
                </html>',
                200
            ),
            'https://example.com/kontakt' => Http::response(
                '<html>
                    <head>
                        <title>Kontakt</title>
                        <meta name="description" content="Das ist die Kontaktseite.">
                    </head>
                    <body>
                        <h1>Kontakt</h1>
                    </body>
                </html>',
                200
            ),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertSame('completed', $crawlRun->fresh()->status);
        $this->assertSame(2, $crawlRun->fresh()->pages_crawled);

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com/kontakt',
            'depth' => 1,
        ]);

        $this->assertDatabaseHas('detected_technologies', [
            'crawl_run_id' => $crawlRun->id,
            'type' => 'cms',
            'name' => 'WordPress',
        ]);

        $this->assertCount(2, Page::where('crawl_run_id', $crawlRun->id)->get());
        $this->assertCount(1, Website::all());
    }

    public function test_it_does_not_crawl_external_links(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <head>
                        <title>Startseite</title>
                        <meta name="description" content="Das ist die Startseite.">
                    </head>
                    <body>
                        <h1>Startseite</h1>
                        <a href="https://external.com/kontakt">Extern</a>
                    </body>
                </html>',
                200
            ),
            'https://external.com/kontakt' => Http::response(
                '<html>
                    <head>
                        <title>Extern</title>
                    </head>
                    <body>
                        <h1>Extern</h1>
                    </body>
                </html>',
                200
            ),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertSame('completed', $crawlRun->fresh()->status);
        $this->assertSame(1, $crawlRun->fresh()->pages_crawled);

        $this->assertDatabaseHas('pages', [
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        $this->assertDatabaseMissing('pages', [
            'crawl_run_id' => $crawlRun->id,
            'url' => 'https://external.com/kontakt',
        ]);

        Http::assertNotSent(function ($request) {
            return $request->url() === 'https://external.com/kontakt';
        });
    }

    public function test_it_does_not_crawl_the_same_internal_url_twice(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <head>
                        <title>Startseite</title>
                        <meta name="description" content="Das ist die Startseite.">
                    </head>
                    <body>
                        <h1>Startseite</h1>
                        <a href="/kontakt">Kontakt</a>
                        <a href="/kontakt#formular">Kontakt Formular</a>
                        <a href="https://example.com/kontakt">Kontakt absolut</a>
                    </body>
                </html>',
                200
            ),
            'https://example.com/kontakt' => Http::response(
                '<html>
                    <head>
                        <title>Kontakt</title>
                        <meta name="description" content="Das ist die Kontaktseite.">
                    </head>
                    <body>
                        <h1>Kontakt</h1>
                    </body>
                </html>',
                200
            ),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertSame('completed', $crawlRun->fresh()->status);
        $this->assertSame(2, $crawlRun->fresh()->pages_crawled);

        $this->assertSame(
            1,
            Page::where('crawl_run_id', $crawlRun->id)
                ->where('url', 'https://example.com/kontakt')
                ->count()
        );

        // Only the 2 pages should be crawled (not robots.txt, sitemap, etc)
        // Contact page should only be fetched once despite multiple links
        Http::assertSent(function ($request) {
            return str_contains((string) $request->url(), 'https://example.com/kontakt');
        }, 1);
    }

    public function test_it_persists_decimal_image_dimensions_as_integer_values(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html>
                    <head>
                        <title>Startseite</title>
                        <meta name="description" content="Das ist die Startseite.">
                    </head>
                    <body>
                        <h1>Startseite</h1>
                        <img src="/image.png" alt="Example image" width="1920" height="822.857142857">
                    </body>
                </html>',
                200
            ),
            '*' => Http::response('', 404),
        ]);

        $crawlRun = app(CrawlerService::class)->crawl('https://example.com');

        $this->assertSame('completed', $crawlRun->fresh()->status);

        $this->assertDatabaseHas('images', [
            'width' => 1920,
            'height' => 823,
        ]);
    }
}