<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;

final class CrawlRunPagesController extends Controller
{
    public function index(CrawlRun $crawlRun): JsonResponse
    {
        $pages = $crawlRun->pages()
            ->orderBy('depth')
            ->orderBy('url')
            ->get()
            ->map(fn ($page) => [
                'id' => $page->id,
                'url' => $page->url,
                'statusCode' => $page->status_code,
                'title' => $page->title,
                'h1' => $page->headings()->where('level', 1)->first()?->text,
                'canonicalHref' => $page->canonical_href,
                'canonicalUrl' => $page->canonical_url,
                'redirectCount' => $page->redirect_count,
                'depth' => $page->depth,
                'issueCount' => $page->issues()->count(),
            ]);

        return response()->json([
            'data' => $pages,
        ]);
    }

    public function show(CrawlRun $crawlRun, int $pageId): JsonResponse
    {
        $page = $crawlRun->pages()
            ->with(['headings', 'images', 'links', 'issues'])
            ->findOrFail($pageId);

        $h1Headings = $page->headings->where('level', 1);
        $imageCount = $page->images->count();
        $imagesWithoutAlt = $page->images->filter(fn ($image) => blank($image->alt))->count();
        $internalLinksCount = $page->links->where('is_internal', true)->count();
        $externalLinksCount = $page->links->where('is_internal', false)->count();

        return response()->json([
            'data' => [
                'id' => $page->id,
                'url' => $page->url,
                'requestedUrl' => $page->requested_url,
                'finalUrl' => $page->final_url,
                'statusCode' => $page->status_code,
                'depth' => $page->depth,
                
                // SEO
                'title' => $page->title,
                'titleLength' => $page->title ? mb_strlen($page->title) : null,
                'metaDescription' => $page->meta_description,
                'metaDescriptionLength' => $page->meta_description ? mb_strlen($page->meta_description) : null,
                'h1' => $h1Headings->first()?->text,
                'h1Count' => $h1Headings->count(),
                
                // Canonical
                'canonicalHref' => $page->canonical_href,
                'canonicalUrl' => $page->canonical_url,
                'canonicalCount' => $page->canonical_count,
                
                // Redirects
                'redirectCount' => $page->redirect_count,
                'redirectChain' => $page->redirect_chain,
                
                // Headings
                'headings' => $page->headings->map(fn ($h) => [
                    'level' => $h->level,
                    'text' => $h->text,
                ])->values(),
                
                // Links
                'internalLinksCount' => $internalLinksCount,
                'externalLinksCount' => $externalLinksCount,
                
                // Images
                'imageCount' => $imageCount,
                'imagesWithoutAlt' => $imagesWithoutAlt,
                'images' => $page->images->map(fn ($img) => [
                    'src' => $img->src,
                    'alt' => $img->alt,
                ])->values(),
                
                // Technical
                'htmlSizeBytes' => $page->html ? strlen($page->html) : null,
                'responseTimeMs' => $page->response_time_ms,
                
                // Issues
                'issues' => $page->issues->map(fn ($issue) => [
                    'code' => $issue->code,
                    'severity' => $issue->severity,
                    'message' => $issue->message,
                ])->sortBy([
                    fn ($a, $b) => ['error' => 0, 'warning' => 1, 'info' => 2][$a['severity']] <=> ['error' => 0, 'warning' => 1, 'info' => 2][$b['severity']],
                ])->values(),
                
                'createdAt' => $page->created_at?->toISOString(),
            ],
        ]);
    }
}
