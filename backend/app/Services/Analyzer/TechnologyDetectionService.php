<?php

namespace App\Services\Analyzer;

use App\Models\CrawlRun;
use App\Models\DetectedTechnology;

class TechnologyDetectionService
{
    public function __construct(
        private readonly WebsiteTechnologyAnalyzer $technologyAnalyzer,
    ) {
    }

    public function analyze(CrawlRun $crawlRun): void
    {
        $crawlRun->load(['website', 'pages']);

        DetectedTechnology::query()
            ->where('crawl_run_id', $crawlRun->id)
            ->delete();

        foreach ($crawlRun->pages as $page) {
            $detections = $this->technologyAnalyzer->analyze($page->html);

            foreach ($detections as $detection) {
                DetectedTechnology::query()->create([
                    'website_id' => $crawlRun->website_id,
                    'crawl_run_id' => $crawlRun->id,
                    'page_id' => $page->id,
                    'type' => $detection->type,
                    'name' => $detection->name,
                    'confidence' => $detection->confidence,
                    'evidence' => $detection->evidence,
                ]);
            }
        }
    }
}