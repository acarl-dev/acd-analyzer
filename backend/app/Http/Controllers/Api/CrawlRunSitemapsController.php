<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CrawlRunSitemapsController extends Controller
{
    public function index(CrawlRun $crawlRun): JsonResponse
    {
        $sitemaps = $crawlRun->website
            ->sitemaps()
            ->where('crawl_run_id', $crawlRun->id)
            ->with(['childSitemaps'])
            ->withCount('urls')
            ->get()
            ->map(fn ($sitemap) => [
                'id' => $sitemap->id,
                'url' => $sitemap->url,
                'type' => $sitemap->type,
                'statusCode' => $sitemap->status_code,
                'exists' => $sitemap->exists,
                'urlCount' => $sitemap->urls_count,
                'parentSitemapId' => $sitemap->parent_sitemap_id,
                'childSitemapCount' => $sitemap->childSitemaps->count(),
                'fetchedAt' => $sitemap->fetched_at?->toISOString(),
                'error' => $sitemap->error,
            ]);

        // Build tree structure
        $rootSitemaps = $sitemaps->where('parentSitemapId', null)->values();
        $childSitemaps = $sitemaps->where('parentSitemapId', '!=', null)->groupBy('parentSitemapId');

        $buildTree = function ($sitemaps) use (&$buildTree, $childSitemaps) {
            return $sitemaps->map(function ($sitemap) use (&$buildTree, $childSitemaps) {
                $children = $childSitemaps->get($sitemap['id'], collect([]));
                $sitemap['children'] = $buildTree($children)->values()->all();
                return $sitemap;
            });
        };

        $tree = $buildTree($rootSitemaps);

        $totalUrls = $sitemaps->sum('urlCount');

        return response()->json([
            'summary' => [
                'total' => $sitemaps->count(),
                'totalUrls' => $totalUrls,
            ],
            'data' => $tree,
        ]);
    }

    public function show(CrawlRun $crawlRun, int $sitemapId, Request $request): JsonResponse
    {
        $sitemap = $crawlRun->website
            ->sitemaps()
            ->where('crawl_run_id', $crawlRun->id)
            ->findOrFail($sitemapId);

        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('perPage', 50), 500);

        $urls = $sitemap->urls()
            ->orderBy('url')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'sitemap' => [
                'id' => $sitemap->id,
                'url' => $sitemap->url,
                'type' => $sitemap->type,
                'statusCode' => $sitemap->status_code,
            ],
            'pagination' => [
                'currentPage' => $urls->currentPage(),
                'perPage' => $urls->perPage(),
                'total' => $urls->total(),
                'lastPage' => $urls->lastPage(),
            ],
            'data' => $urls->items()->map(fn ($url) => [
                'id' => $url->id,
                'url' => $url->url,
                'normalizedUrl' => $url->normalized_url,
                'lastmod' => $url->lastmod?->toISOString(),
                'changefreq' => $url->changefreq,
                'priority' => $url->priority,
            ]),
        ]);
    }
}
