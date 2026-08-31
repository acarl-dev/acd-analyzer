<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Vimeo extends TechnologySignature
{
    public function name(): string
    {
        return 'Vimeo';
    }

    public function slug(): string
    {
        return 'vimeo';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::VIDEO_MAPS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'player.vimeo.com'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'vimeo.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'vimeo'],
        ];
    }
}
