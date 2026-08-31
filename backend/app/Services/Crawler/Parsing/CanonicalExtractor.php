<?php

namespace App\Services\Crawler\Parsing;

use App\Services\Crawler\Url\UrlNormalizer;
use Symfony\Component\DomCrawler\Crawler;

class CanonicalExtractor
{
    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
    ) {
    }

    /**
     * Extract canonical link tags from HTML
     * 
     * Returns:
     * [
     *   'href' => string|null,      // Raw href from first valid canonical
     *   'url' => string|null,       // Absolute resolved URL from first valid canonical
     *   'count' => int,             // Total number of canonical tags found
     * ]
     */
    public function extract(Crawler $crawler, string $baseUrl): array
    {
        $canonicals = [];

        // Find all <link> tags with rel containing "canonical"
        // Handle variations: rel="canonical", rel="Canonical", rel="canonical alternate"
        $crawler->filter('link')->each(function (Crawler $node) use (&$canonicals) {
            $rel = $node->attr('rel');
            
            if (!$rel) {
                return;
            }

            // Check if rel attribute contains "canonical" (case-insensitive)
            $relParts = preg_split('/\s+/', strtolower($rel));
            
            if (!in_array('canonical', $relParts)) {
                return;
            }

            $href = $node->attr('href');
            
            $canonicals[] = [
                'href' => $href,
                'node' => $node,
            ];
        });

        $count = count($canonicals);

        // No canonical found
        if ($count === 0) {
            return [
                'href' => null,
                'url' => null,
                'count' => 0,
            ];
        }

        // Find first canonical with non-empty href
        $firstValid = null;
        foreach ($canonicals as $canonical) {
            $href = $canonical['href'];
            
            // Skip empty or whitespace-only href
            if ($href === null || trim($href) === '') {
                continue;
            }

            $firstValid = $canonical;
            break;
        }

        // All canonicals have empty href
        if ($firstValid === null) {
            return [
                'href' => '',
                'url' => null,
                'count' => $count,
            ];
        }

        $href = trim($firstValid['href']);

        // Resolve relative URL to absolute URL
        $absoluteUrl = $this->resolveCanonicalUrl($href, $baseUrl);

        return [
            'href' => $href,
            'url' => $absoluteUrl,
            'count' => $count,
        ];
    }

    /**
     * Resolve canonical href to absolute URL
     */
    private function resolveCanonicalUrl(string $href, string $baseUrl): ?string
    {
        // Protocol-relative URL: //example.com/path
        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';
            $href = $scheme . ':' . $href;
        }

        // Check if URL is already absolute (has scheme)
        if (preg_match('/^https?:\/\//i', $href)) {
            // For absolute URLs, don't call normalizeLink as it changes the scheme
            // Just normalize the URL (remove fragment, normalize trailing slash)
            return $this->normalizeAbsoluteCanonical($href);
        }

        // For relative URLs, use normalizeLink
        try {
            return $this->urlNormalizer->normalizeLink($href, $baseUrl);
        } catch (\Exception $e) {
            // If normalization fails (e.g., invalid URL), return null
            return null;
        }
    }

    /**
     * Normalize an absolute canonical URL without changing its scheme
     */
    private function normalizeAbsoluteCanonical(string $url): ?string
    {
        // Parse URL
        $parts = parse_url($url);
        
        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            return null;
        }

        // Rebuild URL without fragment
        $normalized = strtolower($parts['scheme']) . '://';
        $normalized .= strtolower($parts['host']);
        
        if (isset($parts['port']) && (
            ($parts['scheme'] === 'http' && $parts['port'] != 80) ||
            ($parts['scheme'] === 'https' && $parts['port'] != 443)
        )) {
            $normalized .= ':' . $parts['port'];
        }
        
        // Add path if it exists
        if (isset($parts['path'])) {
            $normalized .= $parts['path'];
        }
        
        if (isset($parts['query'])) {
            $normalized .= '?' . $parts['query'];
        }
        
        // Remove trailing slash unless there's a query parameter
        // This includes root URLs: https://example.com/ -> https://example.com
        if (!isset($parts['query']) && str_ends_with($normalized, '/')) {
            $normalized = rtrim($normalized, '/');
        }
        
        // Fragment is intentionally omitted
        
        return $normalized;
    }
}
