<?php

namespace App\Services\Crawler\Download;

use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Renderer\BrowserRendererClient;

class PageContentFetcher
{
    public function __construct(
        private readonly PageDownloader $pageDownloader,
        private readonly BrowserRendererClient $browserRenderer,
    ) {}

    public function fetchHttp(string $url): DownloadedPage
    {
        return $this->pageDownloader->download($url);
    }

    public function fetchRendered(string $url): ?DownloadedPage
    {
        return $this->browserRenderer->renderAsDownloadedPage($url);
    }
}