<?php

namespace App\Services\Crawler\RobotsTxt;

use App\Services\Crawler\Url\UrlNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RobotsTxtFetcher
{
    public function __construct(
        private RobotsTxtParser $parser,
        private UrlNormalizer $urlNormalizer
    ) {}

    /**
     * Fetch and parse robots.txt for a given start URL.
     *
     * @param string $startUrl Base URL of the website (e.g., https://example.com)
     * @return array{url: string, status_code: int|null, exists: bool, content: string|null, sitemaps: array, rules: array, error: string|null}
     */
    public function fetch(string $startUrl): array
    {
        $normalizedBase = $this->urlNormalizer->normalizeStartUrl($startUrl);
        $robotsUrl = rtrim($normalizedBase, '/') . '/robots.txt';

        try {
            $response = Http::timeout(10)->get($robotsUrl);

            $statusCode = $response->status();
            $exists = $statusCode === 200;
            $content = $exists ? $response->body() : null;

            $parsed = $exists && $content
                ? $this->parser->parse($content)
                : ['rules' => [], 'sitemaps' => []];

            // Normalize sitemap URLs
            $normalizedSitemaps = array_map(
                fn($url) => $this->urlNormalizer->normalizeStartUrl($url),
                $parsed['sitemaps']
            );

            return [
                'url' => $robotsUrl,
                'status_code' => $statusCode,
                'exists' => $exists,
                'content' => $content,
                'sitemaps' => $normalizedSitemaps,
                'rules' => $parsed['rules'],
                'error' => null,
            ];
        } catch (ConnectionException $e) {
            return [
                'url' => $robotsUrl,
                'status_code' => null,
                'exists' => false,
                'content' => null,
                'sitemaps' => [],
                'rules' => [],
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'url' => $robotsUrl,
                'status_code' => null,
                'exists' => false,
                'content' => null,
                'sitemaps' => [],
                'rules' => [],
                'error' => 'Fetch failed: ' . $e->getMessage(),
            ];
        }
    }
}
