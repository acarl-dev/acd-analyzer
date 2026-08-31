<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Matomo extends TechnologySignature
{
    public function name(): string
    {
        return 'Matomo';
    }

    public function slug(): string
    {
        return 'matomo';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ANALYTICS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'matomo.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'piwik.js'],
            ['source' => SignalSource::HTML, 'pattern' => '_paq.push'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'matomo'],
            ['source' => SignalSource::HTML, 'pattern' => 'piwik'],
        ];
    }
}
