<?php

namespace Tests\Unit\Services\Renderer;

use App\Services\Renderer\RenderedPageParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RenderedPageParserTest extends TestCase
{
    public function test_it_parses_rendered_html_from_renderer_service(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'url' => 'https://example.com',
                'status' => 200,
                'html' => '<!DOCTYPE html><html><head><title>Rendered Test</title></head><body><h1>Rendered Heading</h1><a href="/kontakt">Kontakt</a><img src="/hero.jpg" alt="Hero"></body></html>',
            ]),
        ]);

        $parsedPage = app(RenderedPageParser::class)->renderAndParse('https://example.com');

        $this->assertNotNull($parsedPage);
        $this->assertSame('https://example.com', $parsedPage->url);
        $this->assertSame('Rendered Test', $parsedPage->title);
        $this->assertCount(1, $parsedPage->headings);
        $this->assertSame(1, $parsedPage->headings[0]['level']);
        $this->assertSame('Rendered Heading', $parsedPage->headings[0]['text']);
        $this->assertCount(1, $parsedPage->links);
        $this->assertCount(1, $parsedPage->images);
    }

    public function test_it_returns_null_when_rendering_fails(): void
    {
        config()->set('services.renderer.url', 'http://renderer:3001');

        Http::fake([
            'http://renderer:3001/render' => Http::response([
                'error' => 'Rendering failed.',
            ], 500),
        ]);

        $parsedPage = app(RenderedPageParser::class)->renderAndParse('https://example.com');

        $this->assertNull($parsedPage);
    }
}