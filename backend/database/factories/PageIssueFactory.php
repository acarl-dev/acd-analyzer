<?php

namespace Database\Factories;

use App\Models\CrawlRun;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageIssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'crawl_run_id' => CrawlRun::factory(),
            'page_id' => Page::factory(),
            'crawl_error_id' => null,
            'code' => 'missing_title',
            'severity' => 'warning',
            'message' => 'Die Seite hat keinen Title.',
        ];
    }
}