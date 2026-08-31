<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Shopify extends TechnologySignature
{
    public function name(): string
    {
        return 'Shopify';
    }

    public function slug(): string
    {
        return 'shopify';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ECOMMERCE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'cdn.shopify.com'],
            ['source' => SignalSource::HTML, 'pattern' => 'Shopify.theme'],
            ['source' => SignalSource::HTML, 'pattern' => 'myshopify.com'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'shopify'],
            ['source' => SignalSource::HTML, 'pattern' => 'Shopify.'],
        ];
    }
}
