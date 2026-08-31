<?php

namespace App\Services\Crawler;

use App\Models\CrawlError;
use App\Models\CrawlRun;
use App\Services\Crawler\Enums\ErrorCode;
use App\Services\Crawler\Enums\ErrorSeverity;
use App\Services\Crawler\Enums\ErrorSource;
use Illuminate\Support\Facades\Log;

class ErrorService
{
    /**
     * Record a crawl error
     */
    public function recordError(
        CrawlRun $crawlRun,
        ErrorCode $code,
        ErrorSource $source,
        string $url,
        string $message,
        ?array $context = null,
        ?int $depth = null,
        ?ErrorSeverity $severity = null
    ): CrawlError {
        // Auto-determine severity if not provided
        if ($severity === null) {
            $severity = $this->determineSeverity($code);
        }

        $error = CrawlError::create([
            'crawl_run_id' => $crawlRun->id,
            'code' => $code->value,
            'severity' => $severity->value,
            'source' => $source->value,
            'url' => $url,
            'message' => $message,
            'context' => $context,
            'depth' => $depth,
            'occurred_at' => now(),
        ]);

        // Log for debugging
        Log::warning("Crawl error recorded", [
            'crawl_run_id' => $crawlRun->id,
            'code' => $code->value,
            'source' => $source->value,
            'url' => $url,
            'severity' => $severity->value,
        ]);

        return $error;
    }

    /**
     * Determine if an error is fatal (should stop the crawl)
     */
    public function isFatal(ErrorCode $code, string $url, string $startUrl): bool
    {
        // Start URL failures are fatal
        if ($this->normalizeUrl($url) === $this->normalizeUrl($startUrl)) {
            return match ($code) {
                ErrorCode::HTTP_4XX,
                ErrorCode::HTTP_5XX,
                ErrorCode::CONNECTION_FAILED,
                ErrorCode::DNS_FAILED,
                ErrorCode::TIMEOUT => true,
                default => false,
            };
        }

        // All other errors are recoverable
        return false;
    }

    /**
     * Determine severity based on error code
     */
    private function determineSeverity(ErrorCode $code): ErrorSeverity
    {
        return match ($code) {
            ErrorCode::HTTP_5XX,
            ErrorCode::CONNECTION_FAILED,
            ErrorCode::DNS_FAILED => ErrorSeverity::HIGH,
            
            ErrorCode::HTTP_4XX,
            ErrorCode::TIMEOUT,
            ErrorCode::REDIRECT_LOOP,
            ErrorCode::REDIRECT_LIMIT_EXCEEDED => ErrorSeverity::MEDIUM,
            
            ErrorCode::RENDERER_TIMEOUT,
            ErrorCode::RENDERER_UNAVAILABLE,
            ErrorCode::RENDERER_FAILED,
            ErrorCode::ROBOTS_FETCH_FAILED,
            ErrorCode::SITEMAP_FETCH_FAILED,
            ErrorCode::SITEMAP_PARSE_FAILED,
            ErrorCode::PARSE_FAILED => ErrorSeverity::LOW,
            
            default => ErrorSeverity::MEDIUM,
        };
    }

    /**
     * Normalize URL for comparison
     */
    private function normalizeUrl(string $url): string
    {
        // Remove trailing slashes and fragments for comparison
        $url = rtrim($url, '/');
        $url = preg_replace('/#.*$/', '', $url);
        return $url;
    }
}
