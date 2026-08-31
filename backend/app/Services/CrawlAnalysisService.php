<?php

namespace App\Services;

use App\Models\CrawlRun;
use App\Models\Page;
use App\Services\Analyzer\PageIssueAnalyzer;
use App\Services\Analyzer\TechnologyDetector;

final class CrawlAnalysisService
{
    private const ANALYZER_VERSION = 'page_issue_analyzer:v1';

    public function __construct(
        private readonly PageIssueAnalyzer $pageIssueAnalyzer,
        private readonly TechnologyDetector $technologyDetector,
    ) {
    }

    public function analyze(CrawlRun $crawlRun): void
    {
        $crawlRun->load([
            'pages.headings',
            'pages.images',
            'pages.links',
            'errors',
        ]);

        $crawlRun->issues()->delete();

        // Analyze pages for issues
        foreach ($crawlRun->pages as $page) {
            $this->analyzePage($crawlRun, $page);
        }

        // Analyze crawl errors
        foreach ($crawlRun->errors as $crawlError) {
            $crawlRun->issues()->create([
                'crawl_error_id' => $crawlError->id,
                'url' => $crawlError->url,
                'code' => 'crawl_error',
                'severity' => 'error',
                'message' => "Die Seite konnte nicht gecrawlt werden: {$crawlError->message}",
                'context' => [
                    'message' => $crawlError->message,
                ],
                'analyzer_version' => self::ANALYZER_VERSION,
            ]);
        }

        // Detect technologies
        $this->technologyDetector->detect($crawlRun);
    }

    private function analyzePage(CrawlRun $crawlRun, Page $page): void
    {
        $issues = $this->pageIssueAnalyzer->analyze([
            'title' => $page->title,
            'meta_description' => $page->meta_description,
            'headings' => $page->headings
                ->map(fn ($heading) => [
                    'level' => $heading->level,
                    'text' => $heading->text,
                ])
                ->values()
                ->all(),
            'images' => $page->images
                ->map(fn ($image) => [
                    'alt' => $image->alt,
                ])
                ->values()
                ->all(),
            'links' => $page->links
                ->map(fn ($link) => [
                    'type' => $link->is_internal ? 'internal' : 'external',
                    'href' => $link->href,
                    'text' => $link->text,
                ])
                ->values()
                ->all(),
            'html' => $page->html,
            'html_size_bytes' => $page->html !== null ? strlen($page->html) : 0,
            'response_time_ms' => $page->response_time_ms,
            'status_code' => $page->status_code,
            'canonical_count' => $page->canonical_count,
            'canonical_href' => $page->canonical_href,
            'canonical_url' => $page->canonical_url,
            'final_url' => $page->final_url,
            'url' => $page->url,
        ]);

        foreach ($issues as $issue) {
            $crawlRun->issues()->create([
                'page_id' => $page->id,
                'url' => $page->url,
                'code' => $issue['code'],
                'severity' => $issue['severity'],
                'message' => $issue['message'],
                'context' => $issue['context'] ?? null,
                'analyzer_version' => self::ANALYZER_VERSION,
            ]);
        }
    }
}