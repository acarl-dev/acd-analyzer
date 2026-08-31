<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Joomla extends TechnologySignature
{
    public function name(): string
    {
        return 'Joomla';
    }

    public function slug(): string
    {
        return 'joomla';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CMS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'Joomla', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => '/media/jui/'],
            ['source' => SignalSource::HTML, 'pattern' => '/components/com_'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'joomla'],
            ['source' => SignalSource::HTML, 'pattern' => '/templates/'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::META) {
            if (preg_match('/Joomla!\s+([\d.]+)/i', $signal->context ?? '', $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
