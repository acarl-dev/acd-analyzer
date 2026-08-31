<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class MetaPixel extends TechnologySignature
{
    public function name(): string
    {
        return 'Meta Pixel (Facebook)';
    }

    public function slug(): string
    {
        return 'meta-pixel';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::MARKETING;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'connect.facebook.net'],
            ['source' => SignalSource::HTML, 'pattern' => 'fbq('],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => '_fbp'],
        ];
    }
}
