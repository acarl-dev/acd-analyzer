<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CrawlRunIssuesController extends Controller
{
    public function index(CrawlRun $crawlRun, Request $request): JsonResponse
    {
        $query = $crawlRun->issues()
            ->with(['page']);

        // Filter by severity
        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }

        // Filter by code
        if ($code = $request->query('code')) {
            $query->where('code', $code);
        }

        // Filter by page
        if ($pageId = $request->query('pageId')) {
            $query->where('page_id', $pageId);
        }

        $issues = $query->orderByRaw("
            CASE severity 
                WHEN 'error' THEN 0 
                WHEN 'warning' THEN 1 
                WHEN 'info' THEN 2 
                ELSE 3 
            END
        ")
            ->orderBy('code')
            ->orderBy('page_id')
            ->get()
            ->map(fn ($issue) => [
                'id' => $issue->id,
                'code' => $issue->code,
                'severity' => $issue->severity,
                'message' => $issue->message,
                'pageId' => $issue->page_id,
                'pageUrl' => $issue->page?->url,
                'crawlErrorId' => $issue->crawl_error_id,
            ]);

        // Group issues by code for summary
        $groupedByCode = $issues->groupBy('code')->map(fn ($group) => [
            'code' => $group->first()['code'],
            'severity' => $group->first()['severity'],
            'message' => $group->first()['message'],
            'count' => $group->count(),
        ])->sortByDesc('count')->values();

        $summary = [
            'total' => $issues->count(),
            'errors' => $issues->where('severity', 'error')->count(),
            'warnings' => $issues->where('severity', 'warning')->count(),
            'infos' => $issues->where('severity', 'info')->count(),
            'uniqueCodes' => $issues->pluck('code')->unique()->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'groupedByCode' => $groupedByCode,
            'data' => $issues->values(),
        ]);
    }
}
