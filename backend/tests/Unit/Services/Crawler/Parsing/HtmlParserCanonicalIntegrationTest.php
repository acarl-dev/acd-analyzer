<?php

namespace Tests\Unit\Services\Crawler\Parsing;

use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Crawler\Parsing\HtmlParser;
use Tests\TestCase;

class HtmlParserCanonicalIntegrationTest extends TestCase
{
    public function test_html_parser_extracts_canonical(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="https://example.com/"></head>
<body>Test</body>
</html>
HTML;

        $downloadedPage = new DownloadedPage(
            requestedUrl: 'https://example.com/',
            finalUrl: 'https://example.com/',
            statusCode: 200,
            html: $html,
            responseTimeMs: 100,
            redirectCount: 0,
            redirectChain: null,
        );

        $parser = app(HtmlParser::class);
        $parsedPage = $parser->parse($downloadedPage);

        $this->assertSame('https://example.com/', $parsedPage->canonicalHref);
        // UrlNormalizer removes trailing slash, so canonical_url is normalized
        $this->assertSame('https://example.com', $parsedPage->canonicalUrl);
        $this->assertSame(1, $parsedPage->canonicalCount);
    }
}
