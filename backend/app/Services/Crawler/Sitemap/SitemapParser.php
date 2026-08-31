<?php

namespace App\Services\Crawler\Sitemap;

class SitemapParser
{
    /**
     * Parse sitemap XML content.
     *
     * @param string $content XML content
     * @return array{type: string, urls: array, sitemaps: array, error: string|null}
     */
    public function parse(string $content): array
    {
        if (trim($content) === '') {
            return [
                'type' => 'unknown',
                'urls' => [],
                'sitemaps' => [],
                'error' => 'Empty content',
            ];
        }

        // Suppress XML errors and handle them manually
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            $errorMessage = 'Invalid XML';
            if (!empty($errors)) {
                $errorMessage .= ': ' . $errors[0]->message;
            }
            
            return [
                'type' => 'unknown',
                'urls' => [],
                'sitemaps' => [],
                'error' => $errorMessage,
            ];
        }

        // Register namespace if present
        $namespaces = $xml->getNamespaces(true);
        $ns = isset($namespaces['']) ? $namespaces[''] : null;

        // Determine sitemap type
        $rootName = $xml->getName();
        
        if ($rootName === 'urlset' || ($ns && $this->hasUrlElements($xml, $ns))) {
            return $this->parseUrlSet($xml, $ns);
        }
        
        if ($rootName === 'sitemapindex' || ($ns && $this->hasSitemapElements($xml, $ns))) {
            return $this->parseSitemapIndex($xml, $ns);
        }

        return [
            'type' => 'unknown',
            'urls' => [],
            'sitemaps' => [],
            'error' => 'Unknown sitemap format: ' . $rootName,
        ];
    }

    /**
     * Parse urlset sitemap.
     *
     * @param \SimpleXMLElement $xml
     * @param string|null $ns Namespace
     * @return array
     */
    private function parseUrlSet(\SimpleXMLElement $xml, ?string $ns): array
    {
        $urls = [];
        
        $urlElements = $ns 
            ? $xml->children($ns)->url 
            : $xml->url;

        foreach ($urlElements as $urlElement) {
            $urlData = $ns
                ? $urlElement->children($ns)
                : $urlElement;

            $loc = (string)$urlData->loc;
            
            if ($loc === '') {
                continue;
            }

            $urls[] = [
                'loc' => $loc,
                'lastmod' => isset($urlData->lastmod) ? (string)$urlData->lastmod : null,
                'changefreq' => isset($urlData->changefreq) ? (string)$urlData->changefreq : null,
                'priority' => isset($urlData->priority) ? (float)$urlData->priority : null,
            ];
        }

        return [
            'type' => 'urlset',
            'urls' => $urls,
            'sitemaps' => [],
            'error' => null,
        ];
    }

    /**
     * Parse sitemap index.
     *
     * @param \SimpleXMLElement $xml
     * @param string|null $ns Namespace
     * @return array
     */
    private function parseSitemapIndex(\SimpleXMLElement $xml, ?string $ns): array
    {
        $sitemaps = [];
        
        $sitemapElements = $ns 
            ? $xml->children($ns)->sitemap 
            : $xml->sitemap;

        foreach ($sitemapElements as $sitemapElement) {
            $sitemapData = $ns
                ? $sitemapElement->children($ns)
                : $sitemapElement;

            $loc = (string)$sitemapData->loc;
            
            if ($loc === '') {
                continue;
            }

            $sitemaps[] = [
                'loc' => $loc,
                'lastmod' => isset($sitemapData->lastmod) ? (string)$sitemapData->lastmod : null,
            ];
        }

        return [
            'type' => 'index',
            'urls' => [],
            'sitemaps' => $sitemaps,
            'error' => null,
        ];
    }

    /**
     * Check if XML has url elements (for namespace detection).
     */
    private function hasUrlElements(\SimpleXMLElement $xml, string $ns): bool
    {
        $children = $xml->children($ns);
        return isset($children->url);
    }

    /**
     * Check if XML has sitemap elements (for namespace detection).
     */
    private function hasSitemapElements(\SimpleXMLElement $xml, string $ns): bool
    {
        $children = $xml->children($ns);
        return isset($children->sitemap);
    }
}
