<?php

namespace App\Services\Crawler\DTO;

class DownloadedPage
{
    public function __construct(
        public readonly string $url,
        public readonly ?int $statusCode,
        public readonly string $html,
        public readonly ?int $responseTimeMs,
    ) {
    }
}
