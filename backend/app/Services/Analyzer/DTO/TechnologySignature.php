<?php

namespace App\Services\Analyzer\DTO;

use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyCategory;
use App\Services\Analyzer\Enums\TechnologyConfidence;

abstract class TechnologySignature
{
    abstract public function name(): string;

    abstract public function slug(): string;

    abstract public function category(): TechnologyCategory;

    /**
     * Define strong signals - typically result in HIGH confidence
     *
     * @return array<array{source: SignalSource, pattern: string, caseSensitive?: bool}>
     */
    abstract public function strongSignals(): array;

    /**
     * Define medium signals - multiple can result in HIGH confidence
     *
     * @return array<array{source: SignalSource, pattern: string, caseSensitive?: bool}>
     */
    public function mediumSignals(): array
    {
        return [];
    }

    /**
     * Define weak signals - alone only result in LOW confidence
     *
     * @return array<array{source: SignalSource, pattern: string, caseSensitive?: bool}>
     */
    public function weakSignals(): array
    {
        return [];
    }

    /**
     * Extract version from signal value if possible
     */
    public function extractVersion(Signal $signal): ?string
    {
        return null;
    }

    /**
     * Calculate confidence based on matched signals
     *
     * @param  array<Signal>  $matchedSignals
     */
    public function calculateConfidence(array $matchedSignals): TechnologyConfidence
    {
        $strongCount = 0;
        $mediumCount = 0;
        $weakCount = 0;

        foreach ($matchedSignals as $signal) {
            if ($this->isStrongSignal($signal)) {
                $strongCount++;
            } elseif ($this->isMediumSignal($signal)) {
                $mediumCount++;
            } else {
                $weakCount++;
            }
        }

        // 1 strong signal = HIGH
        if ($strongCount >= 1) {
            return TechnologyConfidence::HIGH;
        }

        // 2+ medium signals = HIGH
        if ($mediumCount >= 2) {
            return TechnologyConfidence::HIGH;
        }

        // 1 medium signal = MEDIUM
        if ($mediumCount >= 1) {
            return TechnologyConfidence::MEDIUM;
        }

        // Only weak signals = LOW
        return TechnologyConfidence::LOW;
    }

    protected function isStrongSignal(Signal $signal): bool
    {
        foreach ($this->strongSignals() as $strongSignal) {
            if ($this->matchesPattern($signal, $strongSignal)) {
                return true;
            }
        }

        return false;
    }

    protected function isMediumSignal(Signal $signal): bool
    {
        foreach ($this->mediumSignals() as $mediumSignal) {
            if ($this->matchesPattern($signal, $mediumSignal)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesPattern(Signal $signal, array $signalDef): bool
    {
        if ($signal->source !== $signalDef['source']) {
            return false;
        }

        $caseSensitive = $signalDef['caseSensitive'] ?? false;

        if ($caseSensitive) {
            return str_contains($signal->value, $signalDef['pattern']);
        }

        return str_contains(
            strtolower($signal->value),
            strtolower($signalDef['pattern'])
        );
    }
}
