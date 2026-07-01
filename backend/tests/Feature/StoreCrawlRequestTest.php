<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreCrawlRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_default_crawl_limits_when_no_options_are_provided(): void
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
                    </body>
                </html>',
                200
            ),
        ]);

        $response = $this->postJson('/api/crawl', [
            'url' => 'https://example.com',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('crawl_runs', [
            'max_pages' => 10,
            'max_depth' => 1,
            'status' => 'completed',
        ]);
    }

    public function test_it_stores_custom_crawl_limits(): void
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
                    </body>
                </html>',
                200
            ),
        ]);

        $response = $this->postJson('/api/crawl', [
            'url' => 'https://example.com',
            'maxPages' => 5,
            'maxDepth' => 0,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('crawl_runs', [
            'max_pages' => 5,
            'max_depth' => 0,
            'status' => 'completed',
        ]);
    }

    public function test_it_rejects_invalid_crawl_limits(): void
    {
        $response = $this->postJson('/api/crawl', [
            'url' => 'https://example.com',
            'maxPages' => 50,
            'maxDepth' => 5,
        ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'maxPages',
            'maxDepth',
        ]);
    }
}