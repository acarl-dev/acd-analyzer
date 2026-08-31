<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Hotjar extends TechnologySignature
{
    public function name(): string
    {
        return 'Hotjar';
    }

    public function slug(): string
    {
        return 'hotjar';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::MARKETING;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'hotjar.com'],
            ['source' => SignalSource::HTML, 'pattern' => 'hj('],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'hotjar'],
        ];
    }
}
