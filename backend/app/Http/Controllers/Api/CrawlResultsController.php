<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use App\Services\CrawlResultsService;
use Illuminate\Http\JsonResponse;

final class CrawlResultsController extends Controller
{
    public function __construct(
        private readonly CrawlResultsService $crawlResultsService,
    ) {
    }

    public function show(CrawlRun $crawlRun): JsonResponse
    {
        return response()->json(
            $this->crawlResultsService->buildForCrawlRun($crawlRun)
        );
    }
}