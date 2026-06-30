<?php

namespace App\Services;

use App\Models\CrawlRun;
use App\Models\PageIssue;
use App\Models\Website;
use Illuminate\Support\Facades\DB;

final class DashboardSummaryService
{
    public function build(): array
    {
        $totalWebsites = Website::query()->count();
        $totalCrawlRuns = CrawlRun::query()->count();

        $totalIssues = PageIssue::query()->count();

        $issuesBySeverity = [
            'errors' => PageIssue::query()
                ->where('severity', 'error')
                ->count(),
            'warnings' => PageIssue::query()
                ->where('severity', 'warning')
                ->count(),
            'infos' => PageIssue::query()
                ->where('severity', 'info')
                ->count(),
        ];

        $websitesWithIssues = Website::query()
            ->whereHas('crawlRuns.issues')
            ->count();

        $topIssues = PageIssue::query()
            ->select([
                'code',
                'severity',
                DB::raw('MIN(message) as message'),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('code', 'severity')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($issue) => [
                'code' => $issue->code,
                'severity' => $issue->severity,
                'message' => $issue->message,
                'count' => (int) $issue->count,
            ])
            ->values()
            ->all();

        return [
            'totalWebsites' => $totalWebsites,
            'totalCrawlRuns' => $totalCrawlRuns,
            'websitesWithIssues' => $websitesWithIssues,
            'totalIssues' => $totalIssues,
            'issuesBySeverity' => $issuesBySeverity,
            'topIssues' => $topIssues,
        ];
    }
}