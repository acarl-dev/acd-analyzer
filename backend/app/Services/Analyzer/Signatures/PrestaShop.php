<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class PrestaShop extends TechnologySignature
{
    public function name(): string
    {
        return 'PrestaShop';
    }

    public function slug(): string
    {
        return 'prestashop';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ECOMMERCE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'PrestaShop', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'prestashop'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'prestashop'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'prestashop'],
        ];
    }
}
