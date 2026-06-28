<?php

namespace App\Services\Crawler\DTO;

class ParsedPage
{
    public function __construct(
        public readonly string $url,
        public readonly ?int $statusCode,
        public readonly ?string $title,
        public readonly ?string $metaDescription,
        public readonly string $html,
        public readonly ?int $responseTimeMs,
        public readonly array $headings,
        public readonly array $links,
        public readonly array $images,
    ) {
    }
}