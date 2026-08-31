<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class TYPO3 extends TechnologySignature
{
    public function name(): string
    {
        return 'TYPO3';
    }

    public function slug(): string
    {
        return 'typo3';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CMS;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::META, 'pattern' => 'TYPO3', 'case_sensitive' => false],
            ['source' => SignalSource::HTML, 'pattern' => 'typo3conf'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'typo3'],
            ['source' => SignalSource::HTML, 'pattern' => 'typo3temp'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::META) {
            if (preg_match('/TYPO3\s+CMS\s+([\d.]+)/i', $signal->context ?? '', $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
