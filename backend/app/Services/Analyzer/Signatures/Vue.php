<?php

namespace App\Services\Analyzer\Signatures;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;

class Vue extends TechnologySignature
{
    public function name(): string
    {
        return 'Vue.js';
    }

    public function slug(): string
    {
        return 'vue';
    }

    public function category(): TechnologyCategory
    {
        return TechnologyCategory::FRONTEND;
    }

    public function strongSignals(): array
    {
        return [
            ['source' => SignalSource::DOM_ATTRIBUTE, 'pattern' => 'data-v-'],
            ['source' => SignalSource::HTML, 'pattern' => '__VUE__'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'vue.js'],
            ['source' => SignalSource::SCRIPT, 'pattern' => 'vue.runtime'],
        ];
    }

    public function mediumSignals(): array
    {
        return [
            ['source' => SignalSource::SCRIPT, 'pattern' => 'vue@'],
            ['source' => SignalSource::HTML, 'pattern' => 'v-cloak'],
        ];
    }

    public function extractVersion(?Signal $signal): ?string
    {
        if ($signal && $signal->source === SignalSource::SCRIPT) {
            if (preg_match('/vue@([\d.]+)/', $signal->value, $matches)) {
                return $matches[1];
            }
            if (preg_match('/vue\.([\d.]+)\.js/', $signal->value, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
