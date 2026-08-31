<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;

final class CrawlRunRobotsTxtController extends Controller
{
    public function show(CrawlRun $crawlRun): JsonResponse
    {
        $robotsTxt = $crawlRun->website
            ->robotsTxt()
            ->where('crawl_run_id', $crawlRun->id)
            ->first();

        if (!$robotsTxt) {
            return response()->json([
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'url' => $robotsTxt->url,
                'statusCode' => $robotsTxt->status_code,
                'exists' => $robotsTxt->exists,
                'content' => $robotsTxt->content,
                'sitemaps' => $robotsTxt->sitemaps ?? [],
                'rules' => $robotsTxt->rules ?? [],
                'fetchedAt' => $robotsTxt->fetched_at?->toISOString(),
            ],
        ]);
    }
}
