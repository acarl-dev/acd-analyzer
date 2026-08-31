<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Complianz extends TechnologySignature
{
    public function name(): string
    {
        return 'Complianz';
    }

    public function slug(): string
    {
        return 'complianz';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CONSENT;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'complianz'],
            ['source' => SignalSource::HTML, 'pattern' => 'cmplz'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'complianz'],
        ];
    }
}
