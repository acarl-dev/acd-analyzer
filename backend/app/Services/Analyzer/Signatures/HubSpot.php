<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class HubSpot extends TechnologySignature
{
    public function name(): string
    {
        return 'HubSpot';
    }

    public function slug(): string
    {
        return 'hubspot';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::MARKETING;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'hubspot.com'],
            ['source' => SignalSource::HTML, 'pattern' => '_hsq'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'hubspot'],
        ];
    }
}
