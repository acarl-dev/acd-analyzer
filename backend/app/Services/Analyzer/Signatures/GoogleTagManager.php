<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class GoogleTagManager extends TechnologySignature
{
    public function name(): string
    {
        return 'Google Tag Manager';
    }

    public function slug(): string
    {
        return 'google-tag-manager';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ANALYTICS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'googletagmanager.com/gtm.js'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'googletagmanager.com'],
        ];
    }
}
