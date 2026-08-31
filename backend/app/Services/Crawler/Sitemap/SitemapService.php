<?php

namespace App\Services\Crawler\Sitemap;

use App\Models\CrawlRun;
use App\Models\RobotsTxt;
use App\Models\Sitemap;
use App\Models\SitemapUrl;
use App\Models\Website;
use Illuminate\Support\Facades\Http;

class SitemapService
{
    private const FALLBACK_PATHS = [
        '/sitemap.xml',
        '/sitemap_index.xml',
    ];

    public function __construct(
        private SitemapFetcher $fetcher
    ) {}

    /**
     * Discover, fetch, and store sitemaps for a website during a crawl run.
     *
     * @param Website $website
     * @param CrawlRun $crawlRun
     * @return array<Sitemap>
     */
    public function fetchAndStore(Website $website, CrawlRun $crawlRun): array
    {
        $this->fetcher->resetCounters();
        
        $sitemapUrls = $this->discoverSitemapUrls($website, $crawlRun);
        
        $sitemaps = [];
        
        foreach ($sitemapUrls as $url) {
            $result = $this->fetcher->fetch($url);
            $sitemap = $this->storeSitemap($website, $crawlRun, $result);
            $sitemaps[] = $sitemap;
        }

        return $sitemaps;
    }

    /**
     * Discover sitemap URLs from robots.txt or fallback paths.
     *
     * @param Website $website
     * @param CrawlRun $crawlRun
     * @return array<string>
     */
    private function discoverSitemapUrls(Website $website, CrawlRun $crawlRun): array
    {
        // First: Check robots.txt
        $robotsTxt = RobotsTxt::where('crawl_run_id', $crawlRun->id)->first();
        
        if ($robotsTxt && !empty($robotsTxt->sitemaps)) {
            return $robotsTxt->sitemaps;
        }

        // Fallback: Try common paths
        $baseUrl = rtrim($website->url, '/');
        $fallbackUrls = [];

        foreach (self::FALLBACK_PATHS as $path) {
            $url = $baseUrl . $path;
            
            // Quick HEAD request to check existence
            try {
                $response = Http::timeout(5)->head($url);
                if ($response->successful()) {
                    $fallbackUrls[] = $url;
                }
            } catch (\Exception $e) {
                // Ignore and continue
            }
        }

        return $fallbackUrls;
    }

    /**
     * Store sitemap and its URLs recursively.
     *
     * @param Website $website
     * @param CrawlRun $crawlRun
     * @param array $result Fetch result from SitemapFetcher
     * @param int|null $parentSitemapId Parent sitemap ID for nested sitemaps
     * @return Sitemap
     */
    private function storeSitemap(
        Website $website, 
        CrawlRun $crawlRun, 
        array $result, 
        ?int $parentSitemapId = null
    ): Sitemap {
        $sitemap = Sitemap::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => $result['url'],
            'status_code' => $result['status_code'],
            'type' => $result['type'],
            'exists' => $result['exists'],
            'content_type' => $result['content_type'],
            'error' => $result['error'],
            'parent_sitemap_id' => $parentSitemapId,
            'fetched_at' => now(),
        ]);

        // Store URLs
        foreach ($result['urls'] as $urlEntry) {
            SitemapUrl::create([
                'sitemap_id' => $sitemap->id,
                'url' => $urlEntry['url'],
                'normalized_url' => $urlEntry['normalized_url'],
                'lastmod' => $urlEntry['lastmod'] ? $this->parseLastmod($urlEntry['lastmod']) : null,
                'changefreq' => $urlEntry['changefreq'],
                'priority' => $urlEntry['priority'],
            ]);
        }

        // Store child sitemaps recursively
        foreach ($result['child_sitemaps'] as $childResult) {
            $this->storeSitemap($website, $crawlRun, $childResult, $sitemap->id);
        }

        return $sitemap;
    }

    /**
     * Parse lastmod string to timestamp.
     */
    private function parseLastmod(string $lastmod): ?\DateTime
    {
        try {
            return new \DateTime($lastmod);
        } catch (\Exception $e) {
            return null;
        }
    }
}
