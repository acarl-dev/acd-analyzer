<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class TailwindCSS extends TechnologySignature
{
    public function name(): string
    {
        return 'Tailwind CSS';
    }

    public function slug(): string
    {
        return 'tailwind-css';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CSS_UI;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'tailwind'],
            ['source' => SignalSource::HTML, 'pattern' => 'tailwindcss'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'tailwind'],
        ];
    }
}
