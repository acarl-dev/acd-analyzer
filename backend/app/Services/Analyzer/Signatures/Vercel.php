<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Vercel extends TechnologySignature
{
    public function name(): string
    {
        return 'Vercel';
    }

    public function slug(): string
    {
        return 'vercel';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::INFRASTRUCTURE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'vercel', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'vercel.app'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'vercel'],
        ];
    }
}
