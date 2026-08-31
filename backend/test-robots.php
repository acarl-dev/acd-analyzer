<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing robots.txt fetching...\n\n";

// Find a website
$website = \App\Models\Website::first();

if (!$website) {
    echo "❌ No website found in database. Please create one first.\n";
    exit(1);
}

echo "🌐 Testing with website: {$website->url}\n\n";

// Start a crawl
$crawlerService = app(\App\Services\Crawler\CrawlerService::class);
$options = new \App\Services\Crawler\DTO\CrawlOptions(maxPages: 3, maxDepth: 1);

echo "🚀 Starting crawl...\n";
$crawl = $crawlerService->crawl($website->url, $options);
echo "✅ Crawl completed! ID: {$crawl->id}\n\n";

// Check robots.txt
$robots = \App\Models\RobotsTxt::where('crawl_run_id', $crawl->id)->first();

echo "🤖 RobotsTxt Results:\n";
echo "─────────────────────────────────\n";

if (!$robots) {
    echo "❌ NO robots.txt record found!\n";
    exit(1);
}

echo "✅ RobotsTxt record exists!\n";
echo "   URL: {$robots->url}\n";
echo "   Status Code: " . ($robots->status_code ?? 'NULL') . "\n";
echo "   Exists: " . ($robots->exists ? '✓ YES' : '✗ NO') . "\n";
echo "   Fetched At: " . ($robots->fetched_at ?? 'NULL') . "\n";

if ($robots->exists) {
    echo "   Content Length: " . strlen($robots->content ?? '') . " bytes\n";
    $sitemapCount = count($robots->sitemaps ?? []);
    echo "   Sitemaps Found: {$sitemapCount}\n";
    
    if ($sitemapCount > 0) {
        echo "\n   📍 Sitemaps:\n";
        foreach ($robots->sitemaps as $sitemap) {
            echo "      - {$sitemap}\n";
        }
    }
    
    $ruleCount = count($robots->rules ?? []);
    echo "   Rules: {$ruleCount} user-agent(s)\n";
}

// Check sitemaps
echo "\n🗺️  Sitemap Results:\n";
echo "─────────────────────────────────\n";

$sitemaps = \App\Models\Sitemap::where('crawl_run_id', $crawl->id)->get();
echo "Sitemaps found: " . $sitemaps->count() . "\n";

foreach ($sitemaps as $sitemap) {
    echo "   - {$sitemap->url} (Status: {$sitemap->status_code}, URLs: {$sitemap->url_count})\n";
}

echo "\n✅ Test completed!\n";
