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
            'errors',
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

        $issues = $this->buildIssues(
            title: $page->title,
            metaDescription: $page->meta_description,
            h1Count: $h1Headings->count(),
            imagesWithoutAlt: $imagesWithoutAlt,
        );

        return [
            'id' => $page->id,
            'url' => $page->url,
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

            'issues' => $issues,
        ];
    }

    private function mapCrawlError($crawlError): array
    {
        return [
            'id' => null,
            'url' => $crawlError->url,
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

            'issues' => [
                [
                    'code' => 'crawl_error',
                    'severity' => 'error',
                    'message' => "Die Seite konnte nicht gecrawlt werden: {$crawlError->message}",
                ],
            ],
        ];
    }

    private function buildIssues(
        ?string $title,
        ?string $metaDescription,
        int $h1Count,
        int $imagesWithoutAlt,
    ): array {
        $issues = [];

        if (blank($title)) {
            $issues[] = [
                'code' => 'missing_title',
                'severity' => 'error',
                'message' => 'Die Seite hat keinen Title.',
            ];
        }

        if (blank($metaDescription)) {
            $issues[] = [
                'code' => 'missing_meta_description',
                'severity' => 'warning',
                'message' => 'Die Seite hat keine Meta Description.',
            ];
        }

        if ($h1Count === 0) {
            $issues[] = [
                'code' => 'missing_h1',
                'severity' => 'error',
                'message' => 'Die Seite hat keine H1-Überschrift.',
            ];
        }

        if ($h1Count > 1) {
            $issues[] = [
                'code' => 'multiple_h1',
                'severity' => 'warning',
                'message' => 'Die Seite enthält mehrere H1-Überschriften.',
            ];
        }

        if ($imagesWithoutAlt > 0) {
            $issues[] = [
                'code' => 'images_without_alt',
                'severity' => 'warning',
                'message' => "{$imagesWithoutAlt} Bilder haben kein alt-Attribut.",
            ];
        }

        return $issues;
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