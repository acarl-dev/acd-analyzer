<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class WooCommerce extends TechnologySignature
{
    public function name(): string
    {
        return 'WooCommerce';
    }

    public function slug(): string
    {
        return 'woocommerce';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::ECOMMERCE;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'WooCommerce', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'woocommerce'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'woocommerce'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::STYLESHEET, 'pattern' => 'woocommerce'],
            ['source' => SignalSource::HTML, 'pattern' => 'wc-'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::META) {
            if (preg_match('/WooCommerce\s+([\d.]+)/i', $signal->context ?? '', $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
