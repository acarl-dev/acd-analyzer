<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Usercentrics extends TechnologySignature
{
    public function name(): string
    {
        return 'Usercentrics';
    }

    public function slug(): string
    {
        return 'usercentrics';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CONSENT;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'usercentrics'],
            ['source' => SignalSource::HTML, 'pattern' => 'uc.js'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'usercentrics'],
        ];
    }
}
