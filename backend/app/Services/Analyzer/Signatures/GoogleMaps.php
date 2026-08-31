<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class GoogleMaps extends TechnologySignature
{
    public function name(): string
    {
        return 'Google Maps';
    }

    public function slug(): string
    {
        return 'google-maps';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::VIDEO_MAPS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'maps.googleapis.com'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'maps.google.com'],
            ['source' => SignalSource::HTML, 'pattern' => 'google.maps.'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'gm-style'],
        ];
    }
}
