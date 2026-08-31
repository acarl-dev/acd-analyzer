<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class React extends TechnologySignature
{
    public function name(): string
    {
        return 'React';
    }

    public function slug(): string
    {
        return 'react';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::DOM_ATTRIBUTE, 'pattern' => 'data-reactroot'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'react.production.min.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'react.development.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'react-dom'],
        ];
    }
}
