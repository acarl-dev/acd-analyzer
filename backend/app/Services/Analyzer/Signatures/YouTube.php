<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class YouTube extends TechnologySignature
{
    public function name(): string
    {
        return 'YouTube';
    }

    public function slug(): string
    {
        return 'youtube';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::VIDEO_MAPS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'youtube.com/embed'],
            ['source' => SignalSource::HTML, 'pattern' => 'youtube-nocookie.com'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'youtube.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'youtu.be'],
        ];
    }
}
