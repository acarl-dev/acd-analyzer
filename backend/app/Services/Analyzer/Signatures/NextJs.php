<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class NextJs extends TechnologySignature
{
    public function name(): string
    {
        return 'Next.js';
    }

    public function slug(): string
    {
        return 'nextjs';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'contains: __NEXT_DATA__'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '/_next/static/'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'generator: Next.js'],
        ];
    }
}
