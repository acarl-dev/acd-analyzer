<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Netlify extends TechnologySignature
{
    public function name(): string
    {
        return 'Netlify';
    }

    public function slug(): string
    {
        return 'netlify';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::INFRASTRUCTURE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'netlify', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'netlify.app'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'netlify'],
        ];
    }
}
