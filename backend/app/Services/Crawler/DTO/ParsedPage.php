<?php

namespace App\Services\Crawler\DTO;

class ParsedPage
{
    public function __construct(
        public readonly string $url,
        public readonly string $requestedUrl,
        public readonly string $finalUrl,
        public readonly ?int $statusCode,
        public readonly ?string $title,
        public readonly ?string $metaDescription,
        public readonly string $html,
        public readonly ?int $responseTimeMs,
        public readonly int $redirectCount,
        public readonly ?array $redirectChain,
        public readonly array $headings,
        public readonly array $links,
        public readonly array $images,
        public readonly ?string $canonicalHref,
        public readonly ?string $canonicalUrl,
        public readonly int $canonicalCount,
    ) {
    }
}