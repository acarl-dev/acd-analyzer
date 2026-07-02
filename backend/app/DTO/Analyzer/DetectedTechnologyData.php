<?php

namespace App\DTO\Analyzer;

final readonly class DetectedTechnologyData
{
    public function __construct(
        public string $type,
        public string $name,
        public float $confidence,
        public string $evidence,
    ) {
    }
}