<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCrawlRequest;
use App\Http\Resources\CrawlRunResource;
use App\Models\CrawlRun;
use App\Services\Crawler\CrawlerService;
use Illuminate\Http\JsonResponse;

class CrawlController extends Controller
{
    public function index(): JsonResponse
    {
        $crawlRuns = CrawlRun::query()
            ->with('website')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CrawlRun $crawlRun) => [
                'id' => $crawlRun->id,
                'websiteId' => $crawlRun->website_id,
                'siteUrl' => $crawlRun->website?->url,
                'status' => $crawlRun->status,
                'pagesCrawled' => $crawlRun->pages_crawled,
                'startedAt' => $crawlRun->started_at?->toISOString(),
                'finishedAt' => $crawlRun->finished_at?->toISOString(),
                'createdAt' => $crawlRun->created_at?->toISOString(),
            ]);

        return response()->json([
            'data' => $crawlRuns,
        ]);
    }

    public function store(StoreCrawlRequest $request, CrawlerService $crawler): CrawlRunResource
    {
        $crawlRun = $crawler->crawl($request->url());

        return new CrawlRunResource($crawlRun);
    }
}