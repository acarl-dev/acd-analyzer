<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Angular extends TechnologySignature
{
    public function name(): string
    {
        return 'Angular';
    }

    public function slug(): string
    {
        return 'angular';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::DOM_ATTRIBUTE, 'pattern' => 'ng-version'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '@angular/'],
            ['source' => SignalSource::HTML, 'pattern' => 'ng-'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'angular.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'angular.min.js'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::DOM_ATTRIBUTE) {
            if (preg_match('/ng-version["\']?\s*[:=]\s*["\']?([\d.]+)/', $signal->context ?? '', $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
