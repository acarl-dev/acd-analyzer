<?php

namespace App\Services\Crawler;

use App\Models\CrawlRun;
use App\Models\Website;
use App\Services\Crawler\Download\PageContentFetcher;
use App\Services\Crawler\Parsing\HtmlParser;
use App\Services\Crawler\Persistence\CrawlResultPersister;
use App\Services\CrawlAnalysisService;
use App\Services\Crawler\Url\UrlNormalizer;
use App\Services\Crawler\DTO\CrawlOptions;
use App\Services\Analyzer\TechnologyDetectionService;
use App\Services\Crawler\RobotsTxt\RobotsTxtService;
use App\Services\Crawler\Sitemap\SitemapService;

class CrawlerService
{

    public function __construct(
        private readonly PageContentFetcher $contentFetcher,
        private readonly HtmlParser $parser,
        private readonly CrawlResultPersister $persister,
        private readonly CrawlAnalysisService $crawlAnalysisService,
        private readonly UrlNormalizer $urlNormalizer,
        private readonly TechnologyDetectionService $technologyDetectionService,
        private readonly RobotsTxtService $robotsTxtService,
        private readonly SitemapService $sitemapService,
    ) {
    }

    public function crawl(string $url, ?CrawlOptions $options = null): CrawlRun
    {
        $options ??= new CrawlOptions();
        $normalizedUrl = $this->urlNormalizer->normalizeStartUrl($url);
        $host = parse_url($normalizedUrl, PHP_URL_HOST);

        $website = Website::firstOrCreate(
            ['url' => $normalizedUrl],
            ['host' => $host]
        );

        $crawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'running',
            'started_at' => now(),
            'max_pages' => $options->maxPages,
            'max_depth' => $options->maxDepth,
        ]);

        try {
            // Fetch robots.txt before crawling
            $this->robotsTxtService->fetchAndStore($website, $crawlRun);
            
            // Fetch sitemaps (discovers from robots.txt or fallback paths)
            $this->sitemapService->fetchAndStore($website, $crawlRun);

            $queue = [
                ['url' => $normalizedUrl, 'depth' => 0],
            ];

            $visited = [];
            $queued = [$normalizedUrl => true];

            while ($queue !== [] && count($visited) < $options->maxPages) {
                $next = array_shift($queue);

                $currentUrl = $next['url'];
                $currentDepth = $next['depth'];

                if (isset($visited[$currentUrl])) {
                    continue;
                }

                $visited[$currentUrl] = true;

                try {
                    $downloadedPage = $this->contentFetcher->fetchHttp($currentUrl);
                    $parsedPage = $this->parser->parse($downloadedPage);

                    // Mark final URL as visited too if it's different (redirect)
                    if ($downloadedPage->finalUrl !== $currentUrl) {
                        $visited[$downloadedPage->finalUrl] = true;
                    }

                    $this->persister->persist(
                        website: $website,
                        crawlRun: $crawlRun,
                        page: $parsedPage,
                        depth: $currentDepth,
                    );

                    if ($currentDepth >= $options->maxDepth) {
                        continue;
                    }

                    foreach ($parsedPage->links as $link) {
                        $targetUrl = $this->urlNormalizer->normalizeLink(
                            href: $link['href'],
                            baseUrl: $currentUrl,
                        );

                        if ($targetUrl === null) {
                            continue;
                        }

                        if (!$this->urlNormalizer->isInternal($targetUrl, $normalizedUrl)) {
                            continue;
                        }

                        if (isset($visited[$targetUrl])) {
                            continue;
                        }

                        if (isset($queued[$targetUrl])) {
                            continue;
                        }

                        $queue[] = [
                            'url' => $targetUrl,
                            'depth' => $currentDepth + 1,
                        ];

                        $queued[$targetUrl] = true;
                    }
                } catch (\Throwable $exception) {
                    if ($currentDepth === 0) {
                        throw $exception;
                    }

                    $crawlRun->errors()->create([
                        'url' => $currentUrl,
                        'message' => $exception->getMessage(),
                        'depth' => $currentDepth,
                    ]);
                }
            }

            $crawlRun->update([
                'pages_crawled' => $crawlRun->pages()->count(),
            ]);

            $this->crawlAnalysisService->analyze($crawlRun);

            $this->technologyDetectionService->analyze($crawlRun);

            $crawlRun->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

        } catch (\Throwable $exception) {
            $crawlRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $crawlRun->fresh();
    }
}