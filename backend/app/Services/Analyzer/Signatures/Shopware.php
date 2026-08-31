<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Shopware extends TechnologySignature
{
    public function name(): string
    {
        return 'Shopware';
    }

    public function slug(): string
    {
        return 'shopware';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ECOMMERCE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'Shopware', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'shopware'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'shopware'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'shopware'],
        ];
    }
}
