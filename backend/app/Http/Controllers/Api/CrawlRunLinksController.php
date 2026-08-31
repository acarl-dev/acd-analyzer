<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;

final class CrawlRunLinksController extends Controller
{
    public function index(CrawlRun $crawlRun): JsonResponse
    {
        $links = $crawlRun->pages()
            ->with(['links'])
            ->get()
            ->flatMap(fn ($page) => 
                $page->links->map(fn ($link) => [
                    'id' => $link->id,
                    'href' => $link->href,
                    'normalizedUrl' => $link->normalized_url,
                    'text' => $link->text,
                    'isInternal' => $link->is_internal,
                    'statusCode' => $link->status_code,
                    'sourcePageUrl' => $page->url,
                    'sourcePageId' => $page->id,
                ])
            )
            ->sortBy('href')
            ->values();

        $summary = [
            'total' => $links->count(),
            'internal' => $links->where('isInternal', true)->count(),
            'external' => $links->where('isInternal', false)->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $links,
        ]);
    }
}
