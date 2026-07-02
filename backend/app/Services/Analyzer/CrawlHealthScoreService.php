<?php

namespace App\Services\Analyzer;

use Illuminate\Support\Collection;

class CrawlHealthScoreService
{
    private const START_SCORE = 100;

    private const PENALTY_BY_SEVERITY = [
        'error' => 30,
        'warning' => 10,
        'info' => 2,
    ];

    public function calculate(Collection $pages): int
    {
        if ($pages->isEmpty()) {
            return 100;
        }

        $averageScore = $pages
            ->map(fn (array $page) => $this->calculatePageScore(collect($page['issues'])))
            ->average();

        return (int) round($averageScore);
    }

    private function calculatePageScore(Collection $issues): int
    {
        $penalty = $issues
            ->sum(fn ($issue) => self::PENALTY_BY_SEVERITY[$issue['severity']] ?? 0);

        return max(0, self::START_SCORE - $penalty);
    }
}