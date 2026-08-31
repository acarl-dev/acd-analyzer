<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;

final class CrawlRunCanonicalsController extends Controller
{
    public function index(CrawlRun $crawlRun): JsonResponse
    {
        $pages = $crawlRun->pages()
            ->orderBy('url')
            ->get()
            ->map(function ($page) {
                // Determine status from canonical data
                $status = 'self';
                
                if ($page->canonical_href === null && $page->canonical_url === null) {
                    $status = 'missing';
                } elseif ($page->canonical_href === '') {
                    $status = 'empty';
                } elseif ($page->canonical_count > 1) {
                    $status = 'multiple';
                } elseif ($page->canonical_url && $page->canonical_url !== $page->url) {
                    $status = 'other_url';
                } elseif ($page->issues()->where('code', 'invalid_canonical')->exists()) {
                    $status = 'invalid';
                }

                return [
                    'id' => $page->id,
                    'url' => $page->url,
                    'canonicalHref' => $page->canonical_href,
                    'canonicalUrl' => $page->canonical_url,
                    'canonicalCount' => $page->canonical_count,
                    'status' => $status,
                ];
            });

        $summary = [
            'total' => $pages->count(),
            'self' => $pages->where('status', 'self')->count(),
            'otherUrl' => $pages->where('status', 'other_url')->count(),
            'missing' => $pages->where('status', 'missing')->count(),
            'invalid' => $pages->where('status', 'invalid')->count(),
            'multiple' => $pages->where('status', 'multiple')->count(),
            'empty' => $pages->where('status', 'empty')->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $pages,
        ]);
    }
}
