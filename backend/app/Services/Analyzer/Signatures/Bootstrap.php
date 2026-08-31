<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Bootstrap extends TechnologySignature
{
    public function name(): string
    {
        return 'Bootstrap';
    }

    public function slug(): string
    {
        return 'bootstrap';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CSS_UI;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'bootstrap.min.css'],
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'bootstrap.css'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'bootstrap.bundle'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'bootstrap.min.js'],
        ];
    }
}
