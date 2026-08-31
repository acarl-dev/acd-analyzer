<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Astro extends TechnologySignature
{
    public function name(): string
    {
        return 'Astro';
    }

    public function slug(): string
    {
        return 'astro';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'astro', 'case_sensitive' => false],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'astro'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'data-astro-'],
        ];
    }
}
