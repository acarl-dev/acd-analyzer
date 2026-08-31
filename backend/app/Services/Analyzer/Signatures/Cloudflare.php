<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Cloudflare extends TechnologySignature
{
    public function name(): string
    {
        return 'Cloudflare';
    }

    public function slug(): string
    {
        return 'cloudflare';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::INFRASTRUCTURE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'cloudflare.com'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'cdnjs.cloudflare.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'cloudflare'],
        ];
    }
}
