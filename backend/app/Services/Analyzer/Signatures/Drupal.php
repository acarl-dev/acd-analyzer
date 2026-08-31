<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Drupal extends TechnologySignature
{
    public function name(): string
    {
        return 'Drupal';
    }

    public function slug(): string
    {
        return 'drupal';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CMS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'Drupal', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => '/sites/default/files'],
            ['source' => SignalSource::HTML, 'pattern' => 'Drupal.settings'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => '/sites/all/'],
            ['source' => SignalSource::SCRIPT, 'pattern' => '/sites/default/'],
            ['source' => SignalSource::STYLESHEET, 'pattern' => '/sites/all/'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::META) {
            if (preg_match('/Drupal\s+([\d.]+)/i', $signal->context ?? '', $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
