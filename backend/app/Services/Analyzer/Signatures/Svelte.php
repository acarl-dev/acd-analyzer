<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Svelte extends TechnologySignature
{
    public function name(): string
    {
        return 'Svelte';
    }

    public function slug(): string
    {
        return 'svelte';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::DOM_ATTRIBUTE, 'pattern' => 'svelte-'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'svelte'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::HTML, 'pattern' => 'class="svelte-'],
        ];
    }
}
