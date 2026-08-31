<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Cookiebot extends TechnologySignature
{
    public function name(): string
    {
        return 'Cookiebot';
    }

    public function slug(): string
    {
        return 'cookiebot';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CONSENT;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'cookiebot.com'],
            ['source' => SignalSource::HTML, 'pattern' => 'CookieConsent'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'cookiebot'],
        ];
    }
}
