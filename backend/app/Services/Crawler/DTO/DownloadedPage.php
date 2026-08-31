<?php

namespace App\Services\Crawler\DTO;

class DownloadedPage
{
    public function __construct(
        public readonly string $requestedUrl,
        public readonly string $finalUrl,
        public readonly ?int $statusCode,
        public readonly string $html,
        public readonly ?int $responseTimeMs,
        public readonly int $redirectCount = 0,
        public readonly ?array $redirectChain = null,
    ) {
    }
}
