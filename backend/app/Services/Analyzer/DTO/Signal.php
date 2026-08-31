<?php

namespace App\Services\Analyzer\DTO;

use App\Services\Analyzer\Enums\SignalSource;

readonly class Signal
{
    public function __construct(
        public SignalSource $source,
        public string $value,
        public ?string $context = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'value' => $this->value,
            'context' => $this->context,
        ];
    }
}
