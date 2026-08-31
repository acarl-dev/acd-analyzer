<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class jQuery extends TechnologySignature
{
    public function name(): string
    {
        return 'jQuery';
    }

    public function slug(): string
    {
        return 'jquery';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::JS_LIBRARY;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'jquery.min.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'jquery.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'jquery-'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'jQuery'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::SCRIPT) {
            if (preg_match('/jquery[.-]([\d.]+)(?:\.min)?\.js/', $signal->value, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
