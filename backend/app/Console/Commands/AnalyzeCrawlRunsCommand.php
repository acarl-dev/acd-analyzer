<?php

namespace App\Console\Commands;

use App\Models\CrawlRun;
use App\Services\CrawlAnalysisService;
use Illuminate\Console\Command;

class AnalyzeCrawlRunsCommand extends Command
{
    protected $signature = 'acd:analyze-crawl-runs {--id= : Analyze only one crawl run by ID}';

    protected $description = 'Analyze existing crawl runs and persist page issues.';

    public function handle(CrawlAnalysisService $crawlAnalysisService): int
    {
        $crawlRunId = $this->option('id');

        $query = CrawlRun::query()
            ->orderBy('id');

        if ($crawlRunId !== null) {
            $query->whereKey((int) $crawlRunId);
        }

        $crawlRuns = $query->get();

        if ($crawlRuns->isEmpty()) {
            $this->warn('No crawl runs found.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Analyzing %d crawl run(s)...',
            $crawlRuns->count()
        ));

        $bar = $this->output->createProgressBar($crawlRuns->count());
        $bar->start();

        foreach ($crawlRuns as $crawlRun) {
            $crawlAnalysisService->analyze($crawlRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Crawl analysis completed.');

        return self::SUCCESS;
    }
}