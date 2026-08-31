<?php

namespace App\Services\Crawler\Parsing;

use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Crawler\DTO\ParsedPage;
use Symfony\Component\DomCrawler\Crawler;

class HtmlParser
{
    public function parse(DownloadedPage $page): ParsedPage
    {
        $crawler = new Crawler($page->html, $page->finalUrl);

        return new ParsedPage(
            url: $page->finalUrl,
            requestedUrl: $page->requestedUrl,
            finalUrl: $page->finalUrl,
            statusCode: $page->statusCode,
            title: $this->title($crawler),
            metaDescription: $this->metaDescription($crawler),
            html: $page->html,
            responseTimeMs: $page->responseTimeMs,
            redirectCount: $page->redirectCount,
            redirectChain: $page->redirectChain,
            headings: $this->headings($crawler),
            links: $this->links($crawler, $page->finalUrl),
            images: $this->images($crawler),
        );
    }

    private function title(Crawler $crawler): ?string
    {
        if (!$crawler->filter('title')->count()) {
            return null;
        }

        return trim($crawler->filter('title')->text());
    }

    private function metaDescription(Crawler $crawler): ?string
    {
        if (!$crawler->filter('meta[name="description"]')->count()) {
            return null;
        }

        return $crawler
            ->filter('meta[name="description"]')
            ->attr('content');
    }

    private function headings(Crawler $crawler): array
    {
        $headings = [];

        foreach ([1, 2, 3] as $level) {
            $crawler->filter("h{$level}")
                ->each(function (Crawler $node) use (&$headings, $level) {
                    $headings[] = [
                        'level' => $level,
                        'text' => trim($node->text()),
                    ];
                });
        }

        return $headings;
    }

    private function links(Crawler $crawler, string $baseUrl): array
    {
        $links = [];

        $crawler->filter('a')->each(function (Crawler $node) use (&$links) {

            $href = $node->attr('href');

            if (!$href) {
                return;
            }

            $links[] = [
                'href' => $href,
                'text' => trim($node->text('')),
            ];
        });

        return $links;
    }

    private function images(Crawler $crawler): array
    {
        $images = [];

        $crawler->filter('img')->each(function (Crawler $node) use (&$images) {

            $images[] = [
                'src' => $node->attr('src') ?? '',
                'alt' => $node->attr('alt'),
                'width' => $node->attr('width'),
                'height' => $node->attr('height'),
            ];
        });

        return $images;
    }
}