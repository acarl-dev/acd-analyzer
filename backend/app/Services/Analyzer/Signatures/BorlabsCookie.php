<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class BorlabsCookie extends TechnologySignature
{
    public function name(): string
    {
        return 'Borlabs Cookie';
    }

    public function slug(): string
    {
        return 'borlabs-cookie';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CONSENT;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'borlabs-cookie'],
            ['source' => SignalSource::HTML, 'pattern' => 'BorlabsCookie'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'borlabs'],
        ];
    }
}
