<?php

namespace Tests\Unit;

use App\Services\Analyzer\CrawlHealthScoreService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CrawlHealthScoreServiceTest extends TestCase
{
    public function test_it_starts_with_100_when_no_pages_exist(): void
    {
        $service = new CrawlHealthScoreService();

        $score = $service->calculate(collect());

        $this->assertSame(100, $score);
    }

    public function test_it_calculates_average_score_across_pages(): void
    {
        $service = new CrawlHealthScoreService();

        $pages = collect([
            [
                'issues' => [
                    ['severity' => 'warning'],
                    ['severity' => 'info'],
                ],
            ],
            [
                'issues' => [
                    ['severity' => 'error'],
                ],
            ],
        ]);

        $score = $service->calculate($pages);

        $this->assertSame(79, $score);
    }

    public function test_it_never_returns_less_than_zero_for_a_page(): void
    {
        $service = new CrawlHealthScoreService();

        $pages = collect([
            [
                'issues' => Collection::times(10, fn () => ['severity' => 'error'])->all(),
            ],
        ]);

        $score = $service->calculate($pages);

        $this->assertSame(0, $score);
    }

    public function test_it_ignores_unknown_severities(): void
    {
        $service = new CrawlHealthScoreService();

        $pages = collect([
            [
                'issues' => [
                    ['severity' => 'unknown'],
                ],
            ],
        ]);

        $score = $service->calculate($pages);

        $this->assertSame(100, $score);
    }
}