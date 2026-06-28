<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCrawlRequest;
use App\Http\Resources\CrawlRunResource;
use App\Services\Crawler\CrawlerService;

class CrawlController extends Controller
{
    public function store(StoreCrawlRequest $request, CrawlerService $crawler): CrawlRunResource
    {
        $crawlRun = $crawler->crawl($request->url());

        return new CrawlRunResource($crawlRun);
    }
}