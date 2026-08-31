<?php

namespace App\Services\Renderer;

use App\Services\Crawler\DTO\DownloadedPage;
use Illuminate\Support\Facades\Http;

class BrowserRendererClient
{
    public function render(string $url): ?string
    {
        $response = Http::timeout(20)->post($this->baseUrl().'/render', [
            'url' => $url,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $html = $response->json('html');

        if (! is_string($html) || $html === '') {
            return null;
        }

        return $html;
    }

    public function renderAsDownloadedPage(string $url): ?DownloadedPage
    {
        $started = microtime(true);

        $html = $this->render($url);

        if ($html === null) {
            return null;
        }

        $responseTimeMs = (int) ((microtime(true) - $started) * 1000);

        return new DownloadedPage(
            requestedUrl: $url,
            finalUrl: $url,
            statusCode: 200,
            html: $html,
            responseTimeMs: $responseTimeMs,
            redirectCount: 0,
            redirectChain: null,
        );
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.renderer.url', 'http://renderer:3001'), '/');
    }
}