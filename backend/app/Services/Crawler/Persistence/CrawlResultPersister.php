<?php

namespace App\Services\Crawler\Persistence;

use App\Models\CrawlRun;
use App\Models\Page;
use App\Models\Website;
use App\Services\Crawler\DTO\ParsedPage;

class CrawlResultPersister
{
    public function persist(Website $website, CrawlRun $crawlRun, ParsedPage $page, int $depth = 0): Page
    {
        $storedPage = $crawlRun->pages()->create([
            'website_id' => $website->id,
            'url' => $page->url,
            'depth' => $depth,
            'status_code' => $page->statusCode,
            'title' => $page->title,
            'meta_description' => $page->metaDescription,
            'html' => $page->html,
            'response_time_ms' => $page->responseTimeMs,
        ]);

        foreach ($page->headings as $heading) {
            $storedPage->headings()->create($heading);
        }

        foreach ($page->links as $link) {
            $storedPage->links()->create($link);
        }

        foreach ($page->images as $image) {
            $storedPage->images()->create($image);
        }

        foreach ($page->images as $image) {
            $storedPage->images()->create($image);
        }

        return $storedPage;
    }
}