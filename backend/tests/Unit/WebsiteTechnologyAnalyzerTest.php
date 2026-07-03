<?php

namespace Tests\Unit\Services\Analyzer;

use App\Services\Analyzer\WebsiteTechnologyAnalyzer;
use PHPUnit\Framework\TestCase;

class WebsiteTechnologyAnalyzerTest extends TestCase
{
    public function test_it_detects_wordpress_from_asset_paths(): void
    {
        $analyzer = new WebsiteTechnologyAnalyzer();

        $detections = $analyzer->analyze(
            '<html><head><link href="/wp-content/themes/theme/style.css"></head></html>'
        );

        $this->assertDetectionExists($detections, 'cms', 'WordPress');
    }

    public function test_it_detects_typo3_from_asset_paths(): void
    {
        $analyzer = new WebsiteTechnologyAnalyzer();

        $detections = $analyzer->analyze(
            '<html><head><link href="/typo3conf/ext/sitepackage/Resources/Public/app.css"></head></html>'
        );

        $this->assertDetectionExists($detections, 'cms', 'TYPO3');
    }

    public function test_it_detects_nextjs_from_markers(): void
    {
        $analyzer = new WebsiteTechnologyAnalyzer();

        $detections = $analyzer->analyze(
            '<html><body><script id="__NEXT_DATA__" type="application/json">{}</script></body></html>'
        );

        $this->assertDetectionExists($detections, 'frontend_framework', 'Next.js');
    }

    public function test_it_detects_js_heavy_pages(): void
    {
        $analyzer = new WebsiteTechnologyAnalyzer();

        $detections = $analyzer->analyze(
            '<html><body><div id="root"></div><script></script><script></script><script></script></body></html>'
        );

        $this->assertDetectionExists($detections, 'rendering', 'JS-heavy');
    }

    /**
     * @param array<int, object> $detections
     */
    private function assertDetectionExists(array $detections, string $type, string $name): void
    {
        $this->assertNotEmpty(
            array_filter(
                $detections,
                fn (object $detection): bool => $detection->type === $type && $detection->name === $name,
            ),
            "Failed asserting that detection {$type}:{$name} exists."
        );
    }

    public function test_it_detects_wix_from_platform_markers(): void
    {
        $analyzer = new WebsiteTechnologyAnalyzer();

        $detections = $analyzer->analyze(
            '<html><head><script src="https://static.wixstatic.com/services/wix-thunderbolt/app.js"></script></head></html>'
        );

        $this->assertDetectionExists($detections, 'cms', 'Wix');
    }

    public function test_it_detects_script_app_shell_pages_as_js_heavy(): void
    {
        $analyzer = new WebsiteTechnologyAnalyzer();

        $detections = $analyzer->analyze(
            '<html>
                <head>
                    <title>App</title>
                    <script src="/app.js"></script>
                    <script src="/vendor.js"></script>
                </head>
                <body>
                    <p>Loading application. Please enable JavaScript.</p>
                </body>
            </html>'
        );

        $this->assertDetectionExists($detections, 'rendering', 'JS-heavy');
    }
}