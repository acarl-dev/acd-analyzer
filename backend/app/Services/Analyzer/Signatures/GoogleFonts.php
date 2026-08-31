<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class GoogleFonts extends TechnologySignature
{
    public function name(): string
    {
        return 'Google Fonts';
    }

    public function slug(): string
    {
        return 'google-fonts';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FONTS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'fonts.googleapis.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'fonts.googleapis.com'],
        ];
    }
}
