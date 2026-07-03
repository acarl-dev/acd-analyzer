<?php

namespace App\Services\Analyzer;

use App\DTO\Analyzer\DetectedTechnologyData;

class WebsiteTechnologyAnalyzer
{
    /**
     * @return list<DetectedTechnologyData>
     */
    public function analyze(string $html): array
    {
        $detections = [];

        $lowerHtml = strtolower($html);

        if (str_contains($lowerHtml, '/wp-content/') || str_contains($lowerHtml, '/wp-includes/')) {
            $detections[] = new DetectedTechnologyData(
                type: 'cms',
                name: 'WordPress',
                confidence: 0.95,
                evidence: 'Found WordPress asset path in HTML.',
            );
        }

        if (str_contains($lowerHtml, 'typo3conf/') || str_contains($lowerHtml, 'typo3temp/') || str_contains($lowerHtml, '/typo3/')) {
            $detections[] = new DetectedTechnologyData(
                type: 'cms',
                name: 'TYPO3',
                confidence: 0.95,
                evidence: 'Found TYPO3 asset or system path in HTML.',
            );
        }

        if (str_contains($html, '__NEXT_DATA__') || str_contains($lowerHtml, '/_next/static/')) {
            $detections[] = new DetectedTechnologyData(
                type: 'frontend_framework',
                name: 'Next.js',
                confidence: 0.98,
                evidence: 'Found Next.js marker in HTML.',
            );
        }

        if (str_contains($html, '__NUXT__') || str_contains($lowerHtml, '/_nuxt/')) {
            $detections[] = new DetectedTechnologyData(
                type: 'frontend_framework',
                name: 'Nuxt',
                confidence: 0.98,
                evidence: 'Found Nuxt marker in HTML.',
            );
        }

        if ($this->looksJavaScriptHeavy($html, $lowerHtml)) {
            $detections[] = new DetectedTechnologyData(
                type: 'rendering',
                name: 'JS-heavy',
                confidence: 0.70,
                evidence: 'HTML contains app/root markers and script-heavy structure with little visible content.',
            );
        }

        if (
            str_contains($lowerHtml, 'wixstatic.com')
            || str_contains($lowerHtml, 'wix.com')
            || str_contains($lowerHtml, '_parastorage_')
            || str_contains($lowerHtml, 'wix-thunderbolt')
            || str_contains($lowerHtml, 'wix-code')
        ) {
            $detections[] = new DetectedTechnologyData(
                type: 'cms',
                name: 'Wix',
                confidence: 0.95,
                evidence: 'Found Wix asset or platform marker in HTML.',
            );
        }

        return $detections;
    }

    private function looksJavaScriptHeavy(string $html, string $lowerHtml): bool
    {
        $hasAppRoot = str_contains($lowerHtml, 'id="root"')
            || str_contains($lowerHtml, "id='root'")
            || str_contains($lowerHtml, 'id="app"')
            || str_contains($lowerHtml, "id='app'");

        $scriptCount = substr_count($lowerHtml, '<script');
        $linkCount = substr_count($lowerHtml, '<a ');

        $textContent = trim(strip_tags($html));
        $visibleTextLength = mb_strlen(preg_replace('/\s+/', ' ', $textContent) ?? '');

        $hasLoadingHint = str_contains($lowerHtml, 'loading')
            || str_contains($lowerHtml, 'enable javascript')
            || str_contains($lowerHtml, 'please enable javascript')
            || str_contains($lowerHtml, 'javascript');

        if ($hasAppRoot && $scriptCount >= 3 && $visibleTextLength < 500) {
            return true;
        }

        return $scriptCount >= 2
            && $linkCount === 0
            && $visibleTextLength < 1000
            && $hasLoadingHint;
    }

    
}