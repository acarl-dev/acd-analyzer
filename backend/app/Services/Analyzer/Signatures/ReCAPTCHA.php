<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class ReCAPTCHA extends TechnologySignature
{
    public function name(): string
    {
        return 'Google reCAPTCHA';
    }

    public function slug(): string
    {
        return 'recaptcha';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CAPTCHA;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'google.com/recaptcha'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'gstatic.com/recaptcha'],
            ['source' => SignalSource::HTML, 'pattern' => 'g-recaptcha'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'recaptcha'],
        ];
    }
}
