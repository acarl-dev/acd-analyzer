<?php

namespace App\Services\Crawler\DTO;

final readonly class RenderDecision
{
    public function __construct(
        public bool $shouldRender,
        public ?string $reason = null,
    ) {
    }
}
