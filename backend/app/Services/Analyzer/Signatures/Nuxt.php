<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Nuxt extends TechnologySignature
{
    public function name(): string
    {
        return 'Nuxt.js';
    }

    public function slug(): string
    {
        return 'nuxt';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => '__NUXT__'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '/_nuxt/'],
            ['source' => SignalSource::META, 'pattern' => 'nuxt', 'case_sensitive' => false],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::DOM_ATTRIBUTE, 'pattern' => 'data-n-'],
        ];
    }
}
