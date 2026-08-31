<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class AdobeFonts extends TechnologySignature
{
    public function name(): string
    {
        return 'Adobe Fonts';
    }

    public function slug(): string
    {
        return 'adobe-fonts';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FONTS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'use.typekit.net'],
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'use.typekit.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'typekit'],
        ];
    }
}
