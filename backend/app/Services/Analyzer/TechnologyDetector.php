<?php

namespace App\Services\Analyzer;

use App\Models\CrawlRun;
use App\Models\DetectedTechnology;
use App\Services\Analyzer\DTO\Signal;
use App\Services\Analyzer\DTO\TechnologySignature;
use App\Services\Analyzer\Enums\TechnologyConfidence;
use App\Services\Analyzer\Signatures\WordPress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TechnologyDetector
{
    /** @var array<TechnologySignature> */
    protected array $signatures = [];

    public function __construct(
        protected SignalCollector $signalCollector,
    ) {
        $this->loadSignatures();
    }

    /**
     * Detect technologies for a crawl run
     */
    public function detect(CrawlRun $crawlRun): void
    {
        // Delete existing detections for this crawl run
        DetectedTechnology::where('crawl_run_id', $crawlRun->id)->delete();

        // Collect signals from all pages
        $allSignals = $this->collectSignalsFromCrawl($crawlRun);

        if ($allSignals->isEmpty()) {
            return;
        }

        // Match signals against each signature
        $detections = $this->matchSignatures($allSignals, $crawlRun);

        // Persist detections
        foreach ($detections as $detection) {
            DetectedTechnology::create($detection);
        }

        Log::info("Detected {$detections->count()} technologies for CrawlRun {$crawlRun->id}");
    }

    /**
     * Collect all signals from all pages in a crawl
     *
     * @return Collection<array{page_id: int, url: string, signals: Collection<Signal>}>
     */
    protected function collectSignalsFromCrawl(CrawlRun $crawlRun): Collection
    {
        $pageSignals = collect();

        $crawlRun->pages()->chunk(50, function ($pages) use ($pageSignals) {
            foreach ($pages as $page) {
                $signals = $this->signalCollector->collectFromPage($page);

                if ($signals->isNotEmpty()) {
                    $pageSignals->push([
                        'page_id' => $page->id,
                        'url' => $page->url,
                        'signals' => $signals,
                    ]);
                }
            }
        });

        return $pageSignals;
    }

    /**
     * Match collected signals against all signatures
     *
     * @param  Collection<array{page_id: int, url: string, signals: Collection<Signal>}>  $allSignals
     * @return Collection<array>
     */
    protected function matchSignatures(Collection $allSignals, CrawlRun $crawlRun): Collection
    {
        $detections = collect();

        foreach ($this->signatures as $signature) {
            $matchedSignals = collect();
            $pagesWithTechnology = collect();

            // Check signals from all pages
            foreach ($allSignals as $pageData) {
                $pageSignals = $pageData['signals'];

                foreach ($pageSignals as $signal) {
                    if ($this->signalMatchesSignature($signal, $signature)) {
                        $matchedSignals->push($signal);
                        $pagesWithTechnology->push($pageData['url']);
                    }
                }
            }

            // If we found matching signals, create detection
            if ($matchedSignals->isNotEmpty()) {
                $confidence = $signature->calculateConfidence($matchedSignals->all());

                // Skip LOW confidence detections
                if ($confidence === TechnologyConfidence::LOW) {
                    continue;
                }

                $version = $this->extractVersion($signature, $matchedSignals);

                $detections->push([
                    'website_id' => $crawlRun->website_id,
                    'crawl_run_id' => $crawlRun->id,
                    'type' => $signature->category()->value, // Legacy field, same as category
                    'name' => $signature->name(),
                    'slug' => $signature->slug(),
                    'category' => $signature->category()->value,
                    'confidence' => $confidence->value,
                    'version' => $version,
                    'evidence' => $this->buildEvidence($matchedSignals),
                    'sources' => $matchedSignals->pluck('source.value')->unique()->values()->all(),
                    'detected_on_pages' => $pagesWithTechnology->unique()->count(),
                ]);
            }
        }

        return $detections;
    }

    protected function signalMatchesSignature(Signal $signal, TechnologySignature $signature): bool
    {
        // Check strong signals
        foreach ($signature->strongSignals() as $signalDef) {
            if ($this->matchesPattern($signal, $signalDef)) {
                return true;
            }
        }

        // Check medium signals
        foreach ($signature->mediumSignals() as $signalDef) {
            if ($this->matchesPattern($signal, $signalDef)) {
                return true;
            }
        }

        // Check weak signals
        foreach ($signature->weakSignals() as $signalDef) {
            if ($this->matchesPattern($signal, $signalDef)) {
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

    /**
     * @param  Collection<Signal>  $signals
     */
    protected function extractVersion(TechnologySignature $signature, Collection $signals): ?string
    {
        foreach ($signals as $signal) {
            $version = $signature->extractVersion($signal);
            if ($version) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @param  Collection<Signal>  $signals
     */
    protected function buildEvidence(Collection $signals): array
    {
        return $signals
            ->take(5) // Limit evidence to avoid huge JSON
            ->map(fn (Signal $signal) => $signal->toArray())
            ->all();
    }

    protected function loadSignatures(): void
    {
        // Register all technology signatures here
        $this->signatures = [
            // CMS (4)
            new Signatures\WordPress,
            new Signatures\TYPO3,
            new Signatures\Drupal,
            new Signatures\Joomla,

            // Website Builders (3)
            new Signatures\Wix,
            new Signatures\Squarespace,
            new Signatures\Webflow,

            // E-Commerce (5)
            new Signatures\WooCommerce,
            new Signatures\Shopify,
            new Signatures\Shopware,
            new Signatures\Magento,
            new Signatures\PrestaShop,

            // Frontend Frameworks (7)
            new Signatures\React,
            new Signatures\NextJs,
            new Signatures\Vue,
            new Signatures\Nuxt,
            new Signatures\Angular,
            new Signatures\Svelte,
            new Signatures\Astro,

            // JavaScript Libraries (1)
            new Signatures\jQuery,

            // CSS/UI Frameworks (2)
            new Signatures\Bootstrap,
            new Signatures\TailwindCSS,

            // Analytics (3)
            new Signatures\GoogleTagManager,
            new Signatures\GoogleAnalytics,
            new Signatures\Matomo,

            // Consent Management (5)
            new Signatures\Cookiebot,
            new Signatures\Usercentrics,
            new Signatures\BorlabsCookie,
            new Signatures\Complianz,
            new Signatures\ConsentManager,

            // Marketing (3)
            new Signatures\MetaPixel,
            new Signatures\Hotjar,
            new Signatures\HubSpot,

            // Video & Maps (3)
            new Signatures\YouTube,
            new Signatures\Vimeo,
            new Signatures\GoogleMaps,

            // CAPTCHA (3)
            new Signatures\ReCAPTCHA,
            new Signatures\HCaptcha,
            new Signatures\CloudflareTurnstile,

            // Infrastructure (3)
            new Signatures\Cloudflare,
            new Signatures\Vercel,
            new Signatures\Netlify,

            // Fonts (2)
            new Signatures\GoogleFonts,
            new Signatures\AdobeFonts,
        ];
    }
}
