<?php

namespace App\Services\Crawler;

use App\Models\CrawlRun;
use App\Models\Website;
use App\Services\Crawler\Download\PageDownloader;
use App\Services\Crawler\Parsing\HtmlParser;
use App\Services\Crawler\Persistence\CrawlResultPersister;
use Illuminate\Support\Str;
use App\Services\CrawlAnalysisService;

class CrawlerService
{
    public function __construct(
        private readonly PageDownloader $downloader,
        private readonly HtmlParser $parser,
        private readonly CrawlResultPersister $persister,
        private readonly CrawlAnalysisService $crawlAnalysisService,
    ) {
    }

    public function crawl(string $url): CrawlRun
    {
        $normalizedUrl = $this->normalizeUrl($url);
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
            $downloadedPage = $this->downloader->download($normalizedUrl);

            $parsedPage = $this->parser->parse($downloadedPage);

            $this->persister->persist(
                website: $website,
                crawlRun: $crawlRun,
                page: $parsedPage
            );

            $this->crawlAnalysisService->analyze($crawlRun);

            $crawlRun->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $crawlRun->errors()->create([
                'url' => $normalizedUrl,
                'message' => $exception->getMessage(),
            ]);

            $crawlRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $crawlRun->fresh();
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }
}