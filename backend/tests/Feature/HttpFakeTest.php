<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpFakeTest extends TestCase
{
    public function test_http_fake_works(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html><body>Test Content</body></html>', 200),
        ]);

        $response = Http::get('https://example.com/');

        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('Test Content', $response->body());
    }
}
