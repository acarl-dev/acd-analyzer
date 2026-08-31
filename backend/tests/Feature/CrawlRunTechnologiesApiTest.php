<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\DetectedTechnology;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlRunTechnologiesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_technologies_for_crawl_run(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        DetectedTechnology::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'name' => 'WordPress',
            'slug' => 'wordpress',
            'category' => 'cms',
            'confidence' => 'high',
            'version' => '6.8.2',
            'evidence' => [
                ['source' => 'META', 'value' => 'WordPress 6.8.2'],
            ],
            'sources' => ['META', 'HTML'],
            'detected_on_pages' => 3,
        ]);

        DetectedTechnology::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'name' => 'React',
            'slug' => 'react',
            'category' => 'frontend',
            'confidence' => 'high',
            'evidence' => [
                ['source' => 'DOM_ATTRIBUTE', 'value' => 'data-reactroot'],
            ],
            'sources' => ['DOM_ATTRIBUTE'],
            'detected_on_pages' => 5,
        ]);

        $response = $this->getJson("/api/crawl-runs/{$crawlRun->id}/technologies");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'category',
                        'confidence',
                        'version',
                        'evidence',
                        'sources',
                        'detectedOnPages',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Check WordPress
        $wordpress = collect($data)->firstWhere('slug', 'wordpress');
        $this->assertNotNull($wordpress);
        $this->assertEquals('WordPress', $wordpress['name']);
        $this->assertEquals('cms', $wordpress['category']);
        $this->assertEquals('high', $wordpress['confidence']);
        $this->assertEquals('6.8.2', $wordpress['version']);
        $this->assertEquals(3, $wordpress['detectedOnPages']);
        $this->assertIsArray($wordpress['evidence']);
        $this->assertIsArray($wordpress['sources']);

        // Check React
        $react = collect($data)->firstWhere('slug', 'react');
        $this->assertNotNull($react);
        $this->assertEquals('React', $react['name']);
        $this->assertEquals('frontend', $react['category']);
        $this->assertEquals('high', $react['confidence']);
        $this->assertNull($react['version']);
        $this->assertEquals(5, $react['detectedOnPages']);
    }

    public function test_returns_empty_array_when_no_technologies_detected(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        $response = $this->getJson("/api/crawl-runs/{$crawlRun->id}/technologies");

        $response->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    public function test_technologies_are_ordered_by_category_and_name(): void
    {
        $website = Website::forceCreate([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);

        $crawlRun = CrawlRun::forceCreate([
            'website_id' => $website->id,
            'status' => 'completed',
            'pages_crawled' => 1,
        ]);

        // Create technologies in random order
        DetectedTechnology::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'name' => 'Vue.js',
            'slug' => 'vue',
            'category' => 'frontend',
            'confidence' => 'high',
            'evidence' => [],
            'sources' => [],
            'detected_on_pages' => 1,
        ]);

        DetectedTechnology::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'name' => 'WordPress',
            'slug' => 'wordpress',
            'category' => 'cms',
            'confidence' => 'high',
            'evidence' => [],
            'sources' => [],
            'detected_on_pages' => 1,
        ]);

        DetectedTechnology::forceCreate([
            'website_id' => $website->id,
            'crawl_run_id' => $crawlRun->id,
            'name' => 'React',
            'slug' => 'react',
            'category' => 'frontend',
            'confidence' => 'high',
            'evidence' => [],
            'sources' => [],
            'detected_on_pages' => 1,
        ]);

        $response = $this->getJson("/api/crawl-runs/{$crawlRun->id}/technologies");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        // Should be ordered by category first, then by name
        // CMS comes before Frontend alphabetically
        $this->assertEquals('wordpress', $data[0]['slug']); // CMS - WordPress
        $this->assertEquals('react', $data[1]['slug']); // Frontend - React (alphabetically before Vue)
        $this->assertEquals('vue', $data[2]['slug']); // Frontend - Vue
    }
}
