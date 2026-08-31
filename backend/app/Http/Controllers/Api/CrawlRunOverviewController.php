<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use App\Services\Analyzer\CrawlHealthScoreService;
use Illuminate\Http\JsonResponse;

final class CrawlRunOverviewController extends Controller
{
    public function __construct(
        private readonly CrawlHealthScoreService $crawlHealthScoreService,
    ) {
    }

    public function show(CrawlRun $crawlRun): JsonResponse
    {
        $crawlRun->load([
            'website',
            'pages.issues',
            'errors.issues',
            'detectedTechnologies',
        ]);

        // Calculate health score
        $scorePages = $crawlRun->pages
            ->map(fn ($page) => [
                'issues' => $page->issues
                    ->map(fn ($issue) => ['severity' => $issue->severity])
                    ->values()
                    ->all(),
            ])
            ->concat(
                $crawlRun->errors->map(fn ($crawlError) => [
                    'issues' => $crawlError->issues
                        ->map(fn ($issue) => ['severity' => $issue->severity])
                        ->values()
                        ->all(),
                ])
            )
            ->values();

        $healthScore = $this->crawlHealthScoreService->calculate($scorePages);

        // Count pages
        $totalPages = $crawlRun->pages->count();
        $crawlErrors = $crawlRun->errors->count();

        // Count issues
        $allIssues = $crawlRun->issues;
        $totalIssues = $allIssues->count();
        $errorIssues = $allIssues->where('severity', 'error')->count();
        $warningIssues = $allIssues->where('severity', 'warning')->count();
        $infoIssues = $allIssues->where('severity', 'info')->count();

        // Count links
        $internalLinks = $crawlRun->pages->sum(fn ($page) => 
            $page->links()->where('is_internal', true)->count()
        );
        $externalLinks = $crawlRun->pages->sum(fn ($page) => 
            $page->links()->where('is_internal', false)->count()
        );

        // Count redirects
        $redirectedPages = $crawlRun->pages->where('redirect_count', '>', 0)->count();

        // Count canonical issues
        $canonicalIssues = $allIssues->whereIn('code', [
            'missing_canonical',
            'multiple_canonicals',
            'invalid_canonical',
            'empty_canonical',
            'canonical_to_other_url',
        ])->count();

        // Robots.txt status
        $robotsTxt = $crawlRun->website->robotsTxt()
            ->where('crawl_run_id', $crawlRun->id)
            ->first();
        $robotsTxtExists = $robotsTxt?->exists ?? false;

        // Sitemaps
        $sitemaps = $crawlRun->website->sitemaps()
            ->where('crawl_run_id', $crawlRun->id)
            ->get();
        $sitemapCount = $sitemaps->count();
        $sitemapUrlCount = $sitemaps->sum(fn ($sitemap) => $sitemap->urls()->count());

        // Technologies
        $technologyCount = $crawlRun->detectedTechnologies->unique('name')->count();

        return response()->json([
            'data' => [
                'crawlRunId' => $crawlRun->id,
                'websiteId' => $crawlRun->website_id,
                'siteUrl' => $crawlRun->website?->url,
                'status' => $crawlRun->status,
                'startedAt' => $crawlRun->started_at?->toISOString(),
                'finishedAt' => $crawlRun->finished_at?->toISOString(),
                'healthScore' => $healthScore,
                'metrics' => [
                    'crawledPages' => $totalPages,
                    'issues' => $totalIssues,
                    'errorIssues' => $errorIssues,
                    'warningIssues' => $warningIssues,
                    'infoIssues' => $infoIssues,
                    'internalLinks' => $internalLinks,
                    'externalLinks' => $externalLinks,
                    'redirectedPages' => $redirectedPages,
                    'canonicalIssues' => $canonicalIssues,
                    'robotsTxtExists' => $robotsTxtExists,
                    'sitemaps' => $sitemapCount,
                    'sitemapUrls' => $sitemapUrlCount,
                    'technologies' => $technologyCount,
                    'crawlErrors' => $crawlErrors,
                ],
            ],
        ]);
    }
}
