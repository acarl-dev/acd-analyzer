<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Squarespace extends TechnologySignature
{
    public function name(): string
    {
        return 'Squarespace';
    }

    public function slug(): string
    {
        return 'squarespace';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::WEBSITE_BUILDER;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'squarespace.com'],
            ['source' => SignalSource::META, 'pattern' => 'Squarespace', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'squarespace-cdn.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'squarespace'],
        ];
    }
}
