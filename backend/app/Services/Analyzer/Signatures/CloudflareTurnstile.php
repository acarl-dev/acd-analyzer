<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class CloudflareTurnstile extends TechnologySignature
{
    public function name(): string
    {
        return 'Cloudflare Turnstile';
    }

    public function slug(): string
    {
        return 'cloudflare-turnstile';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::CAPTCHA;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'challenges.cloudflare.com/turnstile'],
            ['source' => SignalSource::HTML, 'pattern' => 'cf-turnstile'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'turnstile'],
        ];
    }
}
