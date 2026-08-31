<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class GoogleAnalytics extends TechnologySignature
{
    public function name(): string
    {
        return 'Google Analytics';
    }

    public function slug(): string
    {
        return 'google-analytics';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ANALYTICS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'google-analytics.com/analytics.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'googletagmanager.com/gtag/js'],
            ['source' => SignalSource::HTML, 'pattern' => 'ga(\'create\''],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'gtag('],
            ['source' => SignalSource::HTML, 'pattern' => 'UA-'],
            ['source' => SignalSource::HTML, 'pattern' => 'G-'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        // GA4 vs Universal Analytics
        if ($signal && $signal->source === SignalSource::HTML) {
            if (str_contains($signal->value, 'G-')) {
                return '4';
            }
            if (str_contains($signal->value, 'UA-')) {
                return 'Universal';
            }
        }
        return null;
    }
}
