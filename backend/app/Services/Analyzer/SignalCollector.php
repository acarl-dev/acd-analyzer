<?php

namespace App\Services\Analyzer;

use App\Models\Page;
use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\Enums\SignalSource;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class SignalCollector
{
    /**
     * Collect all signals from a page
     *
     * @return Collection<Signal>
     */
    public function collectFromPage(Page $page): Collection
    {
        $signals = collect();

        if (empty($page->html)) {
            return $signals;
        }

        try {
            $crawler = new Crawler($page->html, $page->url);

            // Meta tags
            $signals = $signals->merge($this->collectMetaTags($crawler));

            // HTML patterns
            $signals = $signals->merge($this->collectHtmlPatterns($page->html));

            // Scripts
            $signals = $signals->merge($this->collectScripts($crawler));

            // Stylesheets
            $signals = $signals->merge($this->collectStylesheets($crawler));

            // DOM attributes
            $signals = $signals->merge($this->collectDomAttributes($crawler));
        } catch (\Throwable $e) {
            // If HTML parsing fails, just return empty signals
            return collect();
        }

        return $signals;
    }

    /**
     * @return Collection<Signal>
     */
    protected function collectMetaTags(Crawler $crawler): Collection
    {
        $signals = collect();

        // Generator meta tag
        try {
            $crawler->filterXPath('//meta[@name="generator"]')->each(function (Crawler $node) use ($signals) {
                $content = $node->attr('content');
                if ($content) {
                    $signals->push(new Signal(
                        SignalSource::META,
                        "generator: {$content}",
                        $content
                    ));
                }
            });
        } catch (\Throwable $e) {
            // Ignore parsing errors
        }

        return $signals;
    }

    /**
     * @return Collection<Signal>
     */
    protected function collectHtmlPatterns(string $html): Collection
    {
        $signals = collect();

        // Common patterns to search for
        $patterns = [
            '/wp-content/',
            '/wp-includes/',
            'wp-json',
            '__NEXT_DATA__',
            '__NUXT__',
            'data-reactroot',
            'ng-version',
            'wixstatic.com',
            'cdn.shopify.com',
            'typo3',
            'joomla',
            'drupal',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($html, $pattern) !== false) {
                $signals->push(new Signal(
                    SignalSource::HTML,
                    "contains: {$pattern}",
                    $pattern
                ));
            }
        }

        return $signals;
    }

    /**
     * @return Collection<Signal>
     */
    protected function collectScripts(Crawler $crawler): Collection
    {
        $signals = collect();

        try {
            $crawler->filter('script[src]')->each(function (Crawler $node) use ($signals) {
                $src = $node->attr('src');
                if ($src) {
                    $signals->push(new Signal(
                        SignalSource::SCRIPT,
                        $src,
                        $this->extractDomain($src)
                    ));
                }
            });
        } catch (\Throwable $e) {
            // Ignore parsing errors
        }

        return $signals;
    }

    /**
     * @return Collection<Signal>
     */
    protected function collectStylesheets(Crawler $crawler): Collection
    {
        $signals = collect();

        try {
            $crawler->filter('link[rel="stylesheet"]')->each(function (Crawler $node) use ($signals) {
                $href = $node->attr('href');
                if ($href) {
                    $signals->push(new Signal(
                        SignalSource::STYLESHEET,
                        $href,
                        $this->extractDomain($href)
                    ));
                }
            });
        } catch (\Throwable $e) {
            // Ignore parsing errors
        }

        return $signals;
    }

    /**
     * @return Collection<Signal>
     */
    protected function collectDomAttributes(Crawler $crawler): Collection
    {
        $signals = collect();

        try {
            // ng-version attribute (Angular)
            $crawler->filter('[ng-version]')->each(function (Crawler $node) use ($signals) {
                $version = $node->attr('ng-version');
                if ($version) {
                    $signals->push(new Signal(
                        SignalSource::DOM_ATTRIBUTE,
                        "ng-version: {$version}",
                        $version
                    ));
                }
            });

            // data-reactroot (React)
            $crawler->filter('[data-reactroot]')->each(function (Crawler $node) use ($signals) {
                $signals->push(new Signal(
                    SignalSource::DOM_ATTRIBUTE,
                    'data-reactroot',
                    null
                ));
            });
        } catch (\Throwable $e) {
            // Ignore parsing errors
        }

        return $signals;
    }

    protected function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);

        return $parsed['host'] ?? null;
    }
}
