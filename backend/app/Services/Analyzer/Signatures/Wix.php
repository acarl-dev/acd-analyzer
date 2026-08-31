<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Wix extends TechnologySignature
{
    public function name(): string
    {
        return 'Wix';
    }

    public function slug(): string
    {
        return 'wix';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::WEBSITE_BUILDER;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'static.wixstatic.com'],
            ['source' => SignalSource::HTML, 'pattern' => 'wix.com'],
            ['source' => SignalSource::META, 'pattern' => 'Wix.com', 'case_sensitive' => false],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'parastorage.com'],
        ];
    }
}
