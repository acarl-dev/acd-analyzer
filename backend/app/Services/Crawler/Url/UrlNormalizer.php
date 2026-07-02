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

        if (filter_var($href, FILTER_VALIDATE_EMAIL)) {
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
            $normalizedUrl = $this->normalizeAbsoluteUrl($href);

            $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME);
            $baseHost = parse_url($baseUrl, PHP_URL_HOST);
            $targetHost = parse_url($normalizedUrl, PHP_URL_HOST);

            if (
                $baseScheme
                && $baseHost
                && $targetHost
                && $this->normalizeHost($baseHost) === $this->normalizeHost($targetHost)
            ) {
                return preg_replace('/^https?:\/\//', $baseScheme . '://', $normalizedUrl);
            }

            return $normalizedUrl;
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
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);
        $startHost = parse_url($startUrl, PHP_URL_HOST);

        if ($urlHost === null || $startHost === null) {
            return false;
        }

        return $this->normalizeHost($urlHost) === $this->normalizeHost($startHost);
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        }

        return $host;
    }

    private function normalizeAbsoluteUrl(string $url): string
    {
        $url = strtok($url, '#') ?: $url;

        return rtrim($url, '/');
    }
}