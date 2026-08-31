<?php

namespace Tests\Feature;

use App\Services\Crawler\Download\PageDownloader;
use App\Services\Crawler\Parsing\HtmlParser;
use App\Services\Crawler\Persistence\CrawlResultPersister;
use App\Models\CrawlRun;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanonicalPersistenceDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_stack_canonical_extraction(): void
    {
        $html = <<<HTML
<html>
<head><link rel="canonical" href="https://example.com/"></head>
<body>Test</body>
</html>
HTML;

        Http::fake([
            'https://example.com/' => Http::response($html, 200),
        ]);

        // Manually instantiate the services like CrawlerService would
        $downloader = app(PageDownloader::class);
        $parser = app(HtmlParser::class);
        $persister = app(CrawlResultPersister::class);

        // Create website and crawl run
        $website = Website::create([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Download HTML
        $downloadedPage = $downloader->download('https://example.com/');
        
        // Verify download worked
        $this->assertStringContainsString('<link rel="canonical"', $downloadedPage->html);

        // Parse HTML
        $parsedPage = $parser->parse($downloadedPage);

        // Verify parsing extracted canonical
        $this->assertSame('https://example.com/', $parsedPage->canonicalHref);
        $this->assertNotNull($parsedPage->canonicalUrl);
        $this->assertSame(1, $parsedPage->canonicalCount);

        // Persist to database
        $storedPage = $persister->persist($website, $crawlRun, $parsedPage, 0);

        // Verify database storage
        $this->assertDatabaseHas('pages', [
            'id' => $storedPage->id,
            'canonical_href' => 'https://example.com/',
            'canonical_url' => $parsedPage->canonicalUrl,
            'canonical_count' => 1,
        ]);
    }
}
