<?php

namespace Tests\Unit\Services\Analyzer;

use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\Enums\TechnologyConfidence;
use App\Services\Analyzer\Signatures\WordPress;
use Tests\TestCase;

class WordPressSignatureTest extends TestCase
{
    public function test_detects_wordpress_from_meta_generator(): void
    {
        $signature = new WordPress;

        $signals = [
            new Signal(SignalSource::META, 'generator: WordPress 6.8', 'WordPress 6.8'),
        ];

        $confidence = $signature->calculateConfidence($signals);

        $this->assertEquals(TechnologyConfidence::HIGH, $confidence);
    }

    public function test_extracts_wordpress_version_from_meta(): void
    {
        $signature = new WordPress;
        $signal = new Signal(SignalSource::META, 'generator: WordPress 6.8.2', 'WordPress 6.8.2');

        $version = $signature->extractVersion($signal);

        $this->assertEquals('6.8.2', $version);
    }

    public function test_detects_wordpress_from_multiple_medium_signals(): void
    {
        $signature = new WordPress;

        $signals = [
            new Signal(SignalSource::HTML, 'contains: /wp-content/', '/wp-content/'),
            new Signal(SignalSource::HTML, 'contains: /wp-includes/', '/wp-includes/'),
        ];

        $confidence = $signature->calculateConfidence($signals);

        $this->assertEquals(TechnologyConfidence::HIGH, $confidence);
    }

    public function test_medium_confidence_with_one_medium_signal(): void
    {
        $signature = new WordPress;

        $signals = [
            new Signal(SignalSource::HTML, 'contains: /wp-content/', '/wp-content/'),
        ];

        $confidence = $signature->calculateConfidence($signals);

        $this->assertEquals(TechnologyConfidence::MEDIUM, $confidence);
    }

    public function test_low_confidence_with_only_weak_signals(): void
    {
        $signature = new WordPress;

        $signals = [
            new Signal(SignalSource::HTML, 'contains: wp-json', 'wp-json'),
        ];

        $confidence = $signature->calculateConfidence($signals);

        $this->assertEquals(TechnologyConfidence::LOW, $confidence);
    }

    public function test_does_not_detect_wordpress_from_unrelated_content(): void
    {
        $signature = new WordPress;

        // A page that mentions WordPress in content but doesn't use it
        $signals = [
            new Signal(SignalSource::HTML, 'contains: We build sites with WordPress', 'We build sites with WordPress'),
        ];

        $confidence = $signature->calculateConfidence($signals);

        // Should not match any signals since none of the patterns match
        $this->assertEquals(TechnologyConfidence::LOW, $confidence);
    }
}
