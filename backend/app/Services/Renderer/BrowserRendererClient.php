<?php

namespace App\Services\Renderer;

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

    private function baseUrl(): string
    {
        return rtrim(config('services.renderer.url', 'http://renderer:3001'), '/');
    }
}