<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\ErrorService;
use App\Services\Crawler\Enums\ErrorCode;
use App\Services\Crawler\Enums\ErrorSource;
use App\Services\Crawler\Enums\ErrorSeverity;
use App\Models\CrawlRun;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErrorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ErrorService $service;
    private CrawlRun $crawlRun;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ErrorService::class);
        
        $website = Website::create([
            'url' => 'https://example.com',
            'host' => 'example.com',
        ]);
        
        $this->crawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function test_records_error_with_all_fields(): void
    {
        $error = $this->service->recordError(
            crawlRun: $this->crawlRun,
            code: ErrorCode::HTTP_4XX,
            source: ErrorSource::HTTP,
            url: 'https://example.com/not-found',
            message: 'HTTP 404',
            context: ['status_code' => 404],
            depth: 1,
            severity: ErrorSeverity::MEDIUM
        );

        $this->assertDatabaseHas('crawl_errors', [
            'crawl_run_id' => $this->crawlRun->id,
            'code' => 'http_4xx',
            'severity' => 'medium',
            'source' => 'http',
            'url' => 'https://example.com/not-found',
            'message' => 'HTTP 404',
            'depth' => 1,
        ]);

        $this->assertEquals(['status_code' => 404], $error->context);
        $this->assertNotNull($error->occurred_at);
    }

    public function test_auto_determines_severity_for_http_5xx(): void
    {
        $error = $this->service->recordError(
            crawlRun: $this->crawlRun,
            code: ErrorCode::HTTP_5XX,
            source: ErrorSource::HTTP,
            url: 'https://example.com/error',
            message: 'HTTP 500'
        );

        $this->assertEquals('high', $error->severity);
    }

    public function test_auto_determines_severity_for_http_4xx(): void
    {
        $error = $this->service->recordError(
            crawlRun: $this->crawlRun,
            code: ErrorCode::HTTP_4XX,
            source: ErrorSource::HTTP,
            url: 'https://example.com/not-found',
            message: 'HTTP 404'
        );

        $this->assertEquals('medium', $error->severity);
    }

    public function test_auto_determines_severity_for_renderer_errors(): void
    {
        $error = $this->service->recordError(
            crawlRun: $this->crawlRun,
            code: ErrorCode::RENDERER_TIMEOUT,
            source: ErrorSource::RENDERER,
            url: 'https://example.com',
            message: 'Renderer timeout'
        );

        $this->assertEquals('low', $error->severity);
    }

    public function test_identifies_fatal_error_for_start_url_http_error(): void
    {
        $isFatal = $this->service->isFatal(
            ErrorCode::HTTP_4XX,
            'https://example.com',
            'https://example.com'
        );

        $this->assertTrue($isFatal);
    }

    public function test_identifies_recoverable_error_for_subpage(): void
    {
        $isFatal = $this->service->isFatal(
            ErrorCode::HTTP_4XX,
            'https://example.com/page',
            'https://example.com'
        );

        $this->assertFalse($isFatal);
    }

    public function test_identifies_fatal_error_for_dns_failure_on_start_url(): void
    {
        $isFatal = $this->service->isFatal(
            ErrorCode::DNS_FAILED,
            'https://example.com',
            'https://example.com'
        );

        $this->assertTrue($isFatal);
    }

    public function test_identifies_recoverable_error_for_renderer_failure(): void
    {
        $isFatal = $this->service->isFatal(
            ErrorCode::RENDERER_FAILED,
            'https://example.com',
            'https://example.com'
        );

        $this->assertFalse($isFatal);
    }

    public function test_normalizes_urls_with_trailing_slashes(): void
    {
        $isFatal = $this->service->isFatal(
            ErrorCode::HTTP_4XX,
            'https://example.com/',
            'https://example.com'
        );

        $this->assertTrue($isFatal);
    }

    public function test_normalizes_urls_with_fragments(): void
    {
        $isFatal = $this->service->isFatal(
            ErrorCode::HTTP_5XX,
            'https://example.com#section',
            'https://example.com'
        );

        $this->assertTrue($isFatal);
    }
}
