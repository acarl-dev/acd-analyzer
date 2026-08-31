<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class HCaptcha extends TechnologySignature
{
    public function name(): string
    {
        return 'hCaptcha';
    }

    public function slug(): string
    {
        return 'hcaptcha';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CAPTCHA;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'hcaptcha.com'],
            ['source' => SignalSource::HTML, 'pattern' => 'h-captcha'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'hcaptcha'],
        ];
    }
}
