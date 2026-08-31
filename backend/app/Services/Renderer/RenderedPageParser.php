<?php

namespace App\Services\Renderer;

use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Crawler\DTO\ParsedPage;
use App\Services\Crawler\Parsing\HtmlParser;

class RenderedPageParser
{
    public function __construct(
        private readonly BrowserRendererClient $renderer,
        private readonly HtmlParser $parser,
    ) {}

    public function renderAndParse(string $url): ?ParsedPage
    {
        $started = microtime(true);

        $html = $this->renderer->render($url);

        $responseTimeMs = (int) ((microtime(true) - $started) * 1000);

        if ($html === null) {
            return null;
        }

        $downloadedPage = new DownloadedPage(
            requestedUrl: $url,
            finalUrl: $url,
            statusCode: 200,
            html: $html,
            responseTimeMs: $responseTimeMs,
        );

        return $this->parser->parse($downloadedPage);
    }
}