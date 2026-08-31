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

    /**
     * Normalize a redirect Location header URL.
     * Unlike normalizeLink(), this preserves the explicit scheme in the Location header.
     * Only resolves relative URLs - absolute URLs keep their scheme unchanged.
     *
     * @param string $location The Location header value
     * @param string $currentUrl The current URL (before redirect)
     * @return string|null The normalized absolute URL, or null if invalid
     */
    public function normalizeRedirectLocation(string $location, string $currentUrl): ?string
    {
        // Check for non-HTTP protocols
        if (Str::contains($location, ':')) {
            $scheme = strtolower(explode(':', $location, 2)[0]);
            if (!in_array($scheme, ['http', 'https'], true)) {
                return null;
            }
        }

        // Resolve relative URLs using RFC 3986 algorithm
        $resolved = $this->resolveUrl($location, $currentUrl);

        if ($resolved === null) {
            return null;
        }

        // Normalize but DON'T change the scheme - redirects should preserve explicit schemes
        return $this->normalizeAbsoluteUrl($resolved);
    }

    public function normalizeLink(string $href, string $baseUrl): ?string
    {
        $href = trim($href);

        if ($href === '') {
            return null;
        }

        // Ignore non-HTTP(S) schemes
        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $href)) {
            $scheme = strtolower(explode(':', $href, 2)[0]);
            if (!in_array($scheme, ['http', 'https'], true)) {
                return null;
            }
        }

        // Ignore email addresses
        if (filter_var($href, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Ignore anchor-only links
        if (Str::startsWith($href, '#')) {
            return null;
        }

        // Resolve relative URLs using RFC 3986 algorithm
        $resolved = $this->resolveUrl($href, $baseUrl);

        if ($resolved === null) {
            return null;
        }

        $normalized = $this->normalizeAbsoluteUrl($resolved);

        // Convert internal HTTP links to match the base URL scheme
        if ($this->isInternal($normalized, $baseUrl)) {
            $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME);
            $normalizedScheme = parse_url($normalized, PHP_URL_SCHEME);
            
            if ($baseScheme && $normalizedScheme && $baseScheme !== $normalizedScheme) {
                $normalized = preg_replace('/^https?:\/\//', $baseScheme . '://', $normalized);
            }
        }

        return $normalized;
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

    /**
     * Resolves a relative URL against a base URL according to RFC 3986.
     */
    private function resolveUrl(string $href, string $baseUrl): ?string
    {
        // Parse the base URL
        $base = parse_url($baseUrl);
        if ($base === false || !isset($base['scheme']) || !isset($base['host'])) {
            return null;
        }

        // If href is absolute, return it
        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }

        // Handle protocol-relative URLs (//example.com/path)
        if (Str::startsWith($href, '//')) {
            return $base['scheme'] . ':' . $href;
        }

        // Parse the relative URL
        $rel = parse_url($href);
        if ($rel === false) {
            return null;
        }

        // If href has a scheme, it's absolute
        if (isset($rel['scheme'])) {
            return $href;
        }

        // Start building the resolved URL
        $resolved = [];
        $resolved['scheme'] = $base['scheme'];
        $resolved['host'] = $base['host'];
        
        if (isset($base['port'])) {
            $resolved['port'] = $base['port'];
        }

        // If href starts with /, it's root-relative
        if (Str::startsWith($href, '/')) {
            $resolved['path'] = $rel['path'] ?? '/';
        } else {
            // Relative path - merge with base path
            $basePath = $base['path'] ?? '/';
            
            // Remove everything after the last / in base path
            $basePath = substr($basePath, 0, strrpos($basePath, '/') + 1);
            
            $relPath = $rel['path'] ?? '';
            $resolved['path'] = $this->removeDotSegments($basePath . $relPath);
        }

        // Query and fragment from relative URL take precedence
        if (isset($rel['query'])) {
            $resolved['query'] = $rel['query'];
        }

        // We intentionally don't include fragment in the resolved URL
        // as fragments are removed in normalizeAbsoluteUrl

        return $this->buildUrl($resolved);
    }

    /**
     * Removes dot segments from a path according to RFC 3986 section 5.2.4.
     */
    private function removeDotSegments(string $path): string
    {
        $output = [];
        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment === '..') {
                array_pop($output);
            } elseif ($segment !== '.' && $segment !== '') {
                $output[] = $segment;
            } elseif ($segment === '' && empty($output)) {
                // Keep leading slash
                $output[] = '';
            }
        }

        $result = implode('/', $output);
        
        // Ensure path starts with / for absolute paths
        if (!empty($path) && $path[0] === '/' && (!empty($result) && $result[0] !== '/')) {
            $result = '/' . $result;
        }

        return $result ?: '/';
    }

    /**
     * Builds a URL from parsed components.
     */
    private function buildUrl(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }

        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }

        if (isset($parts['port'])) {
            // Only include port if it's not the default for the scheme
            $defaultPorts = ['http' => 80, 'https' => 443];
            $scheme = $parts['scheme'] ?? 'http';
            
            if (!isset($defaultPorts[$scheme]) || $defaultPorts[$scheme] !== $parts['port']) {
                $url .= ':' . $parts['port'];
            }
        }

        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        return $url;
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
        // Remove fragment
        $url = strtok($url, '#') ?: $url;

        // Parse and rebuild to normalize
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
            return rtrim($url, '/');
        }

        // Normalize host to lowercase
        $parts['host'] = strtolower($parts['host']);

        // Rebuild URL
        $normalized = $this->buildUrl($parts);

        // Remove all trailing slashes except when query parameters are present
        // This includes root URLs: https://example.com/ -> https://example.com
        $hasQuery = isset($parts['query']);
        
        if (!$hasQuery && str_ends_with($normalized, '/')) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized;
    }
}