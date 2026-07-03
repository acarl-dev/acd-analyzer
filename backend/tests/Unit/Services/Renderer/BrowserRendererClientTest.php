<?php

namespace Tests\Unit\Services\Renderer;

use App\Services\Renderer\BrowserRendererClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrowserRendererClientTest extends TestCase
{
    public function test_it_returns_rendered_html_from_renderer_service(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => '<html><body><h1>Rendered</h1></body></html>',
            ]),
        ]);

        $html = app(BrowserRendererClient::class)->render('https://example.com');

        $this->assertSame('<html><body><h1>Rendered</h1></body></html>', $html);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://renderer:3001/render'
                && $request['url'] === 'https://example.com';
        });
    }

    public function test_it_returns_null_when_renderer_request_fails(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'error' => 'Rendering failed.',
            ], 500),
        ]);

        $html = app(BrowserRendererClient::class)->render('https://example.com');

        $this->assertNull($html);
    }

    public function test_it_returns_null_when_renderer_response_has_no_html(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => '',
            ]),
        ]);

        $html = app(BrowserRendererClient::class)->render('https://example.com');

        $this->assertNull($html);
    }
}