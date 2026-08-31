<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class ConsentManager extends TechnologySignature
{
    public function name(): string
    {
        return 'consentmanager';
    }

    public function slug(): string
    {
        return 'consentmanager';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CONSENT;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'consentmanager.net'],
            ['source' => SignalSource::HTML, 'pattern' => 'cmp.infonline.de'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'consentmanager'],
        ];
    }
}
