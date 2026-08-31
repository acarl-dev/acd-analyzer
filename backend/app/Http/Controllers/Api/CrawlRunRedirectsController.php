<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;

final class CrawlRunRedirectsController extends Controller
{
    public function index(CrawlRun $crawlRun): JsonResponse
    {
        $pagesWithRedirects = $crawlRun->pages()
            ->where('redirect_count', '>', 0)
            ->orderBy('redirect_count', 'desc')
            ->orderBy('url')
            ->get()
            ->map(fn ($page) => [
                'id' => $page->id,
                'requestedUrl' => $page->requested_url,
                'finalUrl' => $page->final_url,
                'url' => $page->url,
                'redirectCount' => $page->redirect_count,
                'redirectChain' => $page->redirect_chain,
                'statusCode' => $page->status_code,
            ]);

        return response()->json([
            'summary' => [
                'total' => $pagesWithRedirects->count(),
                'maxHops' => $pagesWithRedirects->max('redirectCount') ?? 0,
            ],
            'data' => $pagesWithRedirects,
        ]);
    }
}
