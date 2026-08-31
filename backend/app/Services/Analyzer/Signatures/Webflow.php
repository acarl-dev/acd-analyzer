<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Webflow extends TechnologySignature
{
    public function name(): string
    {
        return 'Webflow';
    }

    public function slug(): string
    {
        return 'webflow';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::WEBSITE_BUILDER;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'webflow.com'],
            ['source' => SignalSource::META, 'pattern' => 'Webflow', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'data-wf-'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'webflow'],
        ];
    }
}
