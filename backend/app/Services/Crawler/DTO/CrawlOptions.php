<?php

namespace App\Services\Crawler\DTO;

final readonly class CrawlOptions
{
    public function __construct(
        public int $maxPages = 10,
        public int $maxDepth = 1,
    ) {
    }
}