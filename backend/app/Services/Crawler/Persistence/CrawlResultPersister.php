<?php

namespace App\Services\Crawler\Persistence;

use App\Models\CrawlRun;
use App\Models\Page;
use App\Models\Website;
use App\Services\Crawler\DTO\ParsedPage;
use App\Services\Crawler\Url\UrlNormalizer;

class CrawlResultPersister
{
    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
    ) {
    }

    public function persist(Website $website, CrawlRun $crawlRun, ParsedPage $page, int $depth = 0): Page
    {
        $storedPage = $crawlRun->pages()->create([
            'website_id' => $website->id,
            'url' => $page->url,
            'requested_url' => $page->requestedUrl,
            'final_url' => $page->finalUrl,
            'depth' => $depth,
            'status_code' => $page->statusCode,
            'redirect_count' => $page->redirectCount,
            'redirect_chain' => $page->redirectChain,
            'title' => $page->title,
            'meta_description' => $page->metaDescription,
            'html' => $page->html,
            'response_time_ms' => $page->responseTimeMs,
            'canonical_href' => $page->canonicalHref,
            'canonical_url' => $page->canonicalUrl,
            'canonical_count' => $page->canonicalCount,
            'fetch_method' => $page->fetchMethod,
            'renderer_reason' => $page->rendererReason,
        ]);

        foreach ($page->headings as $heading) {
            $storedPage->headings()->create($heading);
        }

        foreach ($page->links as $link) {
            $normalizedUrl = $this->urlNormalizer->normalizeLink(
                href: $link['href'],
                baseUrl: $page->url,
            );

            // Skip links that cannot be normalized (mailto, tel, javascript, etc.)
            if ($normalizedUrl === null) {
                continue;
            }

            $isInternal = $this->urlNormalizer->isInternal($normalizedUrl, $website->url);

            $storedPage->links()->create([
                'href' => $link['href'],
                'normalized_url' => $normalizedUrl,
                'text' => $link['text'] ?? null,
                'is_internal' => $isInternal,
            ]);
        }

        foreach ($page->images as $image) {
            $storedPage->images()->create([
                'src' => $image['src'] ?? null,
                'alt' => $image['alt'] ?? null,
                'width' => $this->normalizeImageDimension($image['width'] ?? null),
                'height' => $this->normalizeImageDimension($image['height'] ?? null),
            ]);
        }

        return $storedPage;
    }

    private function normalizeImageDimension(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }
}