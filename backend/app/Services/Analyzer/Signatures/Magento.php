<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Magento extends TechnologySignature
{
    public function name(): string
    {
        return 'Magento';
    }

    public function slug(): string
    {
        return 'magento';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ECOMMERCE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'Mage.'],
            ['source' => SignalSource::HTML, 'pattern' => 'magento'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '/mage/'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'magento'],
            ['source' => SignalSource::HTML, 'pattern' => '/skin/frontend/'],
        ];
    }
}
