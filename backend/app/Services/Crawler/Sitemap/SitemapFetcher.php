<?php

namespace App\Services\Crawler\Sitemap;

use App\Services\Crawler\Download\PageDownloader;
use App\Services\Crawler\Url\UrlNormalizer;

class SitemapFetcher
{
    // Configurable limits
    public const MAX_SITEMAPS = 50;
    public const MAX_SITEMAP_DEPTH = 3;
    public const MAX_SITEMAP_URLS = 10000;

    private int $fetchedSitemapCount = 0;
    private int $totalUrlCount = 0;

    public function __construct(
        private PageDownloader $downloader,
        private SitemapParser $parser,
        private UrlNormalizer $urlNormalizer
    ) {}

    /**
     * Fetch and parse a sitemap recursively.
     *
     * @param string $url Sitemap URL
     * @param int $depth Current depth (0 = root)
     * @return array{url: string, status_code: int|null, type: string, exists: bool, content_type: string|null, error: string|null, urls: array, child_sitemaps: array}
     */
    public function fetch(string $url, int $depth = 0): array
    {
        // Check limits
        if ($this->fetchedSitemapCount >= self::MAX_SITEMAPS) {
            return $this->createErrorResult($url, 'MAX_SITEMAPS limit reached');
        }

        if ($depth >= self::MAX_SITEMAP_DEPTH) {
            return $this->createErrorResult($url, 'MAX_SITEMAP_DEPTH limit reached');
        }

        $this->fetchedSitemapCount++;

        // Normalize URL
        $normalizedUrl = $this->urlNormalizer->normalizeStartUrl($url);

        // Fetch sitemap
        $downloadResult = $this->downloader->download($normalizedUrl);

        $statusCode = $downloadResult->statusCode;
        $contentType = null; // PageDownloader doesn't expose headers yet, would need enhancement

        // Check for successful fetch
        if ($statusCode !== 200) {
            return [
                'url' => $downloadResult->finalUrl,
                'status_code' => $statusCode,
                'type' => 'unknown',
                'exists' => false,
                'content_type' => $contentType,
                'error' => $statusCode === 404 ? 'Not found' : "HTTP {$statusCode}",
                'urls' => [],
                'child_sitemaps' => [],
            ];
        }

        // Parse XML
        $parsed = $this->parser->parse($downloadResult->html);

        if ($parsed['error']) {
            return [
                'url' => $downloadResult->finalUrl,
                'status_code' => $statusCode,
                'type' => $parsed['type'],
                'exists' => true,
                'content_type' => $contentType,
                'error' => $parsed['error'],
                'urls' => [],
                'child_sitemaps' => [],
            ];
        }

        $result = [
            'url' => $downloadResult->finalUrl,
            'status_code' => $statusCode,
            'type' => $parsed['type'],
            'exists' => true,
            'content_type' => $contentType,
            'error' => null,
            'urls' => [],
            'child_sitemaps' => [],
        ];

        // Handle urlset
        if ($parsed['type'] === 'urlset') {
            foreach ($parsed['urls'] as $urlEntry) {
                if ($this->totalUrlCount >= self::MAX_SITEMAP_URLS) {
                    $result['error'] = 'MAX_SITEMAP_URLS limit reached';
                    break;
                }

                $normalizedLoc = $this->urlNormalizer->normalizeStartUrl($urlEntry['loc']);

                $result['urls'][] = [
                    'url' => $urlEntry['loc'],
                    'normalized_url' => $normalizedLoc,
                    'lastmod' => $urlEntry['lastmod'],
                    'changefreq' => $urlEntry['changefreq'],
                    'priority' => $urlEntry['priority'],
                ];

                $this->totalUrlCount++;
            }
        }

        // Handle sitemap index (recursive)
        if ($parsed['type'] === 'index') {
            foreach ($parsed['sitemaps'] as $childSitemapEntry) {
                $childResult = $this->fetch($childSitemapEntry['loc'], $depth + 1);
                $result['child_sitemaps'][] = $childResult;
            }
        }

        return $result;
    }

    /**
     * Reset counters for new fetch operation.
     */
    public function resetCounters(): void
    {
        $this->fetchedSitemapCount = 0;
        $this->totalUrlCount = 0;
    }

    /**
     * Get current sitemap count.
     */
    public function getFetchedSitemapCount(): int
    {
        return $this->fetchedSitemapCount;
    }

    /**
     * Get current total URL count.
     */
    public function getTotalUrlCount(): int
    {
        return $this->totalUrlCount;
    }

    /**
     * Create error result structure.
     */
    private function createErrorResult(string $url, string $error): array
    {
        return [
            'url' => $url,
            'status_code' => null,
            'type' => 'unknown',
            'exists' => false,
            'content_type' => null,
            'error' => $error,
            'urls' => [],
            'child_sitemaps' => [],
        ];
    }
}
