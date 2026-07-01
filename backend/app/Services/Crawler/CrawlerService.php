<?php

namespace App\Services\Crawler;

use App\Models\CrawlRun;
use App\Models\Website;
use App\Services\Crawler\Download\PageDownloader;
use App\Services\Crawler\Parsing\HtmlParser;
use App\Services\Crawler\Persistence\CrawlResultPersister;
use App\Services\CrawlAnalysisService;
use App\Services\Crawler\Url\UrlNormalizer;

class CrawlerService
{
    private const MAX_PAGES = 10;
    private const MAX_DEPTH = 1;

    public function __construct(
        private readonly PageDownloader $downloader,
        private readonly HtmlParser $parser,
        private readonly CrawlResultPersister $persister,
        private readonly CrawlAnalysisService $crawlAnalysisService,
        private readonly UrlNormalizer $urlNormalizer,
    ) {
    }

    public function crawl(string $url): CrawlRun
    {
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
        ]);

        try {
            $queue = [
                ['url' => $normalizedUrl, 'depth' => 0],
            ];

            $visited = [];

            while ($queue !== [] && count($visited) < self::MAX_PAGES) {
                $next = array_shift($queue);

                $currentUrl = $next['url'];
                $currentDepth = $next['depth'];

                if (isset($visited[$currentUrl])) {
                    continue;
                }

                $visited[$currentUrl] = true;

                try {
                    $downloadedPage = $this->downloader->download($currentUrl);
                    $parsedPage = $this->parser->parse($downloadedPage);

                    $this->persister->persist(
                        website: $website,
                        crawlRun: $crawlRun,
                        page: $parsedPage,
                        depth: $currentDepth,
                    );

                    if ($currentDepth >= self::MAX_DEPTH) {
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

                        $queue[] = [
                            'url' => $targetUrl,
                            'depth' => $currentDepth + 1,
                        ];
                    }
                } catch (\Throwable $exception) {
                    if ($currentDepth === 0) {
                        throw $exception;
                    }

                    $crawlRun->errors()->create([
                        'url' => $currentUrl,
                        'error_type' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            $crawlRun->update([
                'pages_crawled' => $crawlRun->pages()->count(),
            ]);

            $this->crawlAnalysisService->analyze($crawlRun);

            $crawlRun->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $crawlRun->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $crawlRun->fresh();
    }
}