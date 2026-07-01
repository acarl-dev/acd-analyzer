<?php

namespace App\Services\Crawler\Url;

use Illuminate\Support\Str;

class UrlNormalizer
{
    public function normalizeStartUrl(string $url): string
    {
        $url = trim($url);

        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . $url;
        }

        return $this->normalizeAbsoluteUrl($url);
    }

    public function normalizeLink(string $href, string $baseUrl): ?string
    {
        $href = trim($href);

        if ($href === '') {
            return null;
        }

        if (Str::startsWith($href, ['mailto:', 'tel:', 'javascript:'])) {
            return null;
        }

        if (Str::startsWith($href, '#')) {
            return null;
        }

        if (Str::startsWith($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $this->normalizeAbsoluteUrl($scheme . ':' . $href);
        }

        if (Str::startsWith($href, ['/'])) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            $host = parse_url($baseUrl, PHP_URL_HOST);

            if (!$host) {
                return null;
            }

            return $this->normalizeAbsoluteUrl($scheme . '://' . $host . $href);
        }

        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $this->normalizeAbsoluteUrl($href);
        }

        $base = rtrim($baseUrl, '/');
        $path = parse_url($base, PHP_URL_PATH);

        if ($path && !str_ends_with($base, '/')) {
            $base = substr($base, 0, strrpos($base, '/') ?: strlen($base));
        }

        return $this->normalizeAbsoluteUrl($base . '/' . ltrim($href, '/'));
    }

    public function isInternal(string $url, string $startUrl): bool
    {
        return parse_url($url, PHP_URL_HOST) === parse_url($startUrl, PHP_URL_HOST);
    }

    private function normalizeAbsoluteUrl(string $url): string
    {
        $url = strtok($url, '#') ?: $url;

        return rtrim($url, '/');
    }
}