<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCrawlRequest;
use App\Http\Resources\CrawlRunResource;
use App\Models\CrawlRun;
use App\Services\Crawler\CrawlerService;
use Illuminate\Http\JsonResponse;
use App\Services\Crawler\DTO\CrawlOptions;
use App\Services\Analyzer\CrawlHealthScoreService;

class CrawlController extends Controller
{
    public function index(CrawlHealthScoreService $crawlHealthScoreService): JsonResponse
    {
        $crawlRuns = CrawlRun::query()
            ->with([
                'website',
                'pages.issues',
                'errors.issues',
            ])
            ->withCount([
                'issues as total_issues_count',
                'issues as error_issues_count' => fn ($query) => $query->where('severity', 'error'),
                'issues as warning_issues_count' => fn ($query) => $query->where('severity', 'warning'),
                'issues as info_issues_count' => fn ($query) => $query->where('severity', 'info'),
            ])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (CrawlRun $crawlRun) use ($crawlHealthScoreService) {
                $scorePages = $crawlRun->pages
                    ->map(fn ($page) => [
                        'issues' => $page->issues
                            ->map(fn ($issue) => [
                                'severity' => $issue->severity,
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->concat(
                        $crawlRun->errors->map(fn ($crawlError) => [
                            'issues' => $crawlError->issues
                                ->map(fn ($issue) => [
                                    'severity' => $issue->severity,
                                ])
                                ->values()
                                ->all(),
                        ])
                    )
                    ->values();

                return [
                    'id' => $crawlRun->id,
                    'websiteId' => $crawlRun->website_id,
                    'siteUrl' => $crawlRun->website?->url,
                    'status' => $crawlRun->status,
                    'pagesCrawled' => $crawlRun->pages_crawled,
                    'startedAt' => $crawlRun->started_at?->toISOString(),
                    'finishedAt' => $crawlRun->finished_at?->toISOString(),
                    'createdAt' => $crawlRun->created_at?->toISOString(),
                    'healthScore' => $crawlHealthScoreService->calculate($scorePages),
                    'issueSummary' => [
                        'total' => $crawlRun->total_issues_count,
                        'errors' => $crawlRun->error_issues_count,
                        'warnings' => $crawlRun->warning_issues_count,
                        'infos' => $crawlRun->info_issues_count,
                    ],
                ];
            });

        return response()->json([
            'data' => $crawlRuns,
        ]);
    }

    public function store(
        StoreCrawlRequest $request,
        CrawlerService $crawler,
    ): CrawlRunResource {
        $options = new CrawlOptions(
            maxPages: $request->maxPages(),
            maxDepth: $request->maxDepth(),
        );

        $crawlRun = $crawler->crawl(
            url: $request->url(),
            options: $options,
        );

        return new CrawlRunResource($crawlRun);
    }
}