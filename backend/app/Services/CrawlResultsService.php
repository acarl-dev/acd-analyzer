<?php

namespace App\Services;

use App\Models\CrawlRun;
use Illuminate\Support\Collection;
final class CrawlResultsService
{
    public function buildForCrawlRun(CrawlRun $crawlRun): array
    {
        $crawlRun->load([
            'website',
            'pages.headings',
            'pages.images',
            'pages.links',
            'pages.issues',
            'errors.issues',
        ]);

        $pageResults = $crawlRun->pages
            ->map(fn ($page) => $this->mapPage($page));

        $errorResults = $crawlRun->errors
            ->map(fn ($crawlError) => $this->mapCrawlError($crawlError));

        $pages = $pageResults
            ->concat($errorResults)
            ->values();

        return [
            'crawlRunId' => $crawlRun->id,
            'websiteId' => $crawlRun->website_id,
            'siteUrl' => $crawlRun->website?->url,
            'summary' => $this->buildSummary($pages),
            'pages' => $pages,
        ];
    }

    private function mapIssues(Collection $issues): array
    {
        $severityOrder = [
            'error' => 0,
            'warning' => 1,
            'info' => 2,
        ];

        return $issues
            ->sortBy([
                fn ($a, $b) => ($severityOrder[$a->severity] ?? 99) <=> ($severityOrder[$b->severity] ?? 99),
                fn ($a, $b) => $a->code <=> $b->code,
            ])
            ->map(fn ($issue) => [
                'code' => $issue->code,
                'severity' => $issue->severity,
                'message' => $issue->message,
            ])
            ->values()
            ->all();
    }

    private function mapPage($page): array
    {
        $h1Headings = $page->headings->where('level', 1);

        $imageCount = $page->images->count();

        $imagesWithoutAlt = $page->images
            ->filter(fn ($image) => blank($image->alt))
            ->count();

        $internalLinksCount = $page->links
            ->where('is_internal', true)
            ->count();

        $externalLinksCount = $page->links
            ->where('is_internal', false)
            ->count();

                $issues = $page->issues
                    ->map(fn ($issue) => [
                        'code' => $issue->code,
                        'severity' => $issue->severity,
                        'message' => $issue->message,
                    ])
                    ->values()
                    ->all();

        return [
            'id' => $page->id,
            'url' => $page->url,
            'depth' => $page->depth,
            'httpStatus' => $page->status_code,
            'crawledAt' => $page->created_at?->toISOString(),

            'title' => $page->title,
            'titleLength' => $page->title !== null ? mb_strlen($page->title) : null,

            'metaDescription' => $page->meta_description,
            'metaDescriptionLength' => $page->meta_description !== null
                ? mb_strlen($page->meta_description)
                : null,

            'h1' => $h1Headings->first()?->text,
            'h1Count' => $h1Headings->count(),

            'imageCount' => $imageCount,
            'imagesWithoutAlt' => $imagesWithoutAlt,

            'internalLinksCount' => $internalLinksCount,
            'externalLinksCount' => $externalLinksCount,

            'htmlSizeBytes' => $page->html !== null ? strlen($page->html) : null,

            'hasCrawlError' => false,
            'crawlError' => null,

            'issues' => $this->mapIssues($page->issues),
        ];
    }

    private function mapCrawlError($crawlError): array
    {
        return [
            'id' => null,
            'url' => $crawlError->url,
            'depth' => $crawlError->depth,
            'httpStatus' => null,
            'crawledAt' => $crawlError->created_at?->toISOString(),

            'title' => null,
            'titleLength' => null,

            'metaDescription' => null,
            'metaDescriptionLength' => null,

            'h1' => null,
            'h1Count' => 0,

            'imageCount' => 0,
            'imagesWithoutAlt' => 0,

            'internalLinksCount' => 0,
            'externalLinksCount' => 0,

            'htmlSizeBytes' => null,

            'hasCrawlError' => true,
            'crawlError' => $crawlError->message,

            'issues' => $this->mapIssues($crawlError->issues),
        ];
    }

    private function buildSummary(Collection $pages): array
        {
            $issues = $pages
                ->flatMap(fn ($page) => $page['issues']);

            $errors = $issues
                ->where('severity', 'error')
                ->count();

            $warnings = $issues
                ->where('severity', 'warning')
                ->count();

            $infos = $issues
                ->where('severity', 'info')
                ->count();

            return [
                'totalPages' => $pages->count(),

                'successfulPages' => $pages
                    ->filter(fn ($page) =>
                        $page['hasCrawlError'] === false
                        && $page['httpStatus'] !== null
                        && $page['httpStatus'] >= 200
                        && $page['httpStatus'] < 400
                    )
                    ->count(),

                'failedPages' => $pages
                    ->filter(fn ($page) =>
                        $page['hasCrawlError'] === true
                        || (
                            $page['httpStatus'] !== null
                            && $page['httpStatus'] >= 400
                        )
                    )
                    ->count(),

                'pagesWithIssues' => $pages
                    ->filter(fn ($page) => count($page['issues']) > 0)
                    ->count(),

                'totalIssues' => $issues->count(),
                'errors' => $errors,
                'warnings' => $warnings,
                'infos' => $infos,
            ];
        }
}