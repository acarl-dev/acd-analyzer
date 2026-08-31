<?php

namespace App\Services\Crawler\RobotsTxt;

use App\Models\CrawlRun;
use App\Models\RobotsTxt;
use App\Models\Website;

class RobotsTxtService
{
    public function __construct(
        private RobotsTxtFetcher $fetcher
    ) {}

    /**
     * Fetch and store robots.txt for a website during a crawl run.
     *
     * @param Website $website
     * @param CrawlRun $crawlRun
     * @return RobotsTxt
     */
    public function fetchAndStore(Website $website, CrawlRun $crawlRun): RobotsTxt
    {
        $result = $this->fetcher->fetch($website->url);

        return RobotsTxt::create([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'url' => $result['url'],
            'status_code' => $result['status_code'],
            'exists' => $result['exists'],
            'content' => $result['content'],
            'sitemaps' => $result['sitemaps'],
            'rules' => $result['rules'],
            'fetched_at' => now(),
        ]);
    }
}
