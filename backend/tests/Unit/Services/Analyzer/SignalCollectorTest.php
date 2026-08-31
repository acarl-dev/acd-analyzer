<?php

namespace Tests\Unit\Services\Analyzer;

use App\Models\Page;
use App\Services\Analyzer\Enums\SignalSource;
use App\Services\Analyzer\SignalCollector;
use Tests\TestCase;

class SignalCollectorTest extends TestCase
{
    public function test_collects_meta_generator_signal(): void
    {
        $html = '<html><head><meta name="generator" content="WordPress 6.8"></head><body></body></html>';

        $page = new Page([
            'url' => 'https://example.com',
            'html' => $html,
        ]);

        $collector = new SignalCollector;
        $signals = $collector->collectFromPage($page);

        $this->assertCount(1, $signals);
        $this->assertEquals(SignalSource::META, $signals->first()->source);
        $this->assertStringContainsString('WordPress 6.8', $signals->first()->value);
    }

    public function test_collects_html_patterns(): void
    {
        $html = '<html><body><script src="/wp-content/themes/theme/script.js"></script></body></html>';

        $page = new Page([
            'url' => 'https://example.com',
            'html' => $html,
        ]);

        $collector = new SignalCollector;
        $signals = $collector->collectFromPage($page);

        $htmlSignals = $signals->filter(fn ($s) => $s->source === SignalSource::HTML);

        $this->assertGreaterThan(0, $htmlSignals->count());
        $this->assertTrue($htmlSignals->contains(fn ($s) => str_contains($s->value, '/wp-content/')));
    }

    public function test_collects_script_sources(): void
    {
        $html = '<html><body><script src="https://www.googletagmanager.com/gtm.js?id=GTM-XXX"></script></body></html>';

        $page = new Page([
            'url' => 'https://example.com',
            'html' => $html,
        ]);

        $collector = new SignalCollector;
        $signals = $collector->collectFromPage($page);

        $scriptSignals = $signals->filter(fn ($s) => $s->source === SignalSource::SCRIPT);

        $this->assertGreaterThan(0, $scriptSignals->count());
        $this->assertTrue($scriptSignals->contains(fn ($s) => str_contains($s->value, 'googletagmanager.com')));
    }

    public function test_collects_stylesheet_sources(): void
    {
        $html = '<html><head><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto"></head><body></body></html>';

        $page = new Page([
            'url' => 'https://example.com',
            'html' => $html,
        ]);

        $collector = new SignalCollector;
        $signals = $collector->collectFromPage($page);

        $stylesheetSignals = $signals->filter(fn ($s) => $s->source === SignalSource::STYLESHEET);

        $this->assertGreaterThan(0, $stylesheetSignals->count());
        $this->assertTrue($stylesheetSignals->contains(fn ($s) => str_contains($s->value, 'fonts.googleapis.com')));
    }

    public function test_collects_dom_attributes(): void
    {
        $html = '<html ng-version="19.2.1"><body></body></html>';

        $page = new Page([
            'url' => 'https://example.com',
            'html' => $html,
        ]);

        $collector = new SignalCollector;
        $signals = $collector->collectFromPage($page);

        $domSignals = $signals->filter(fn ($s) => $s->source === SignalSource::DOM_ATTRIBUTE);

        $this->assertGreaterThan(0, $domSignals->count());
        $this->assertTrue($domSignals->contains(fn ($s) => str_contains($s->value, 'ng-version')));
    }

    public function test_returns_empty_collection_for_invalid_html(): void
    {
        $page = new Page([
            'url' => 'https://example.com',
            'html' => null,
        ]);

        $collector = new SignalCollector;
        $signals = $collector->collectFromPage($page);

        $this->assertCount(0, $signals);
    }
}
