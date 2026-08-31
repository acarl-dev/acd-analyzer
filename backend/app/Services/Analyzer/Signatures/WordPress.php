<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class WordPress extends TechnologySignature
{
    public function name(): string
    {
        return 'WordPress';
    }

    public function slug(): string
    {
        return 'wordpress';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CMS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'generator: WordPress'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'contains: /wp-content/'],
            ['source' => SignalSource::HTML, 'pattern' => 'contains: /wp-includes/'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '/wp-includes/'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '/wp-content/'],
        ];
    }

    public function weakSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'contains: wp-json'],
        ];
    }

    public function extractVersion(Signal $signal): ?string
    {
        // Extract version from meta generator tag
        if ($signal->source === SignalSource::META && str_contains($signal->value, 'generator: WordPress')) {
            if (preg_match('/WordPress\s+([\d.]+)/i', $signal->value, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
