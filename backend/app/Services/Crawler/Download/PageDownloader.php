<?php

namespace App\Services\Crawler\Download;

use App\Services\Crawler\DTO\DownloadedPage;
use App\Services\Crawler\Url\UrlNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class PageDownloader
{
    private const MAX_REDIRECTS = 10;

    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
    ) {
    }

    public function download(string $url): DownloadedPage
    {
        $started = microtime(true);
        $redirectChain = [];
        $redirectCount = 0;
        $currentUrl = $url;
        $finalResponse = null;

        // Follow redirects manually to track the chain
        for ($i = 0; $i < self::MAX_REDIRECTS; $i++) {
            $response = Http::withoutRedirecting()
                ->timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Alan Carl Digital Analyzer/0.1',
                ])
                ->get($currentUrl);

            $statusCode = $response->status();

            // Check if this is a redirect
            if ($this->isRedirect($statusCode)) {
                $location = $response->header('Location');

                if (!$location) {
                    // Redirect without Location header - treat as final response
                    $finalResponse = $response;
                    break;
                }

                // Normalize the location URL (might be relative)
                $nextUrl = $this->urlNormalizer->normalizeRedirectLocation($location, $currentUrl);

                if ($nextUrl === null) {
                    // Invalid location - treat current response as final
                    $finalResponse = $response;
                    break;
                }

                // Record this hop
                $redirectChain[] = [
                    'from_url' => $currentUrl,
                    'status_code' => $statusCode,
                    'location' => $location,
                    'to_url' => $nextUrl,
                ];

                $redirectCount++;
                $currentUrl = $nextUrl;
            } else {
                // Not a redirect - this is the final response
                $finalResponse = $response;
                break;
            }
        }

        // If we hit max redirects without a non-redirect response, use the last response
        if ($finalResponse === null) {
            $finalResponse = $response ?? throw new \RuntimeException('No response received');
        }

        $responseTime = (int) ((microtime(true) - $started) * 1000);

        return new DownloadedPage(
            requestedUrl: $url,
            finalUrl: $currentUrl,
            statusCode: $finalResponse->status(),
            html: $finalResponse->body(),
            responseTimeMs: $responseTime,
            redirectCount: $redirectCount,
            redirectChain: $redirectCount > 0 ? $redirectChain : null,
        );
    }

    private function isRedirect(int $statusCode): bool
    {
        return in_array($statusCode, [301, 302, 303, 307, 308], true);
    }
}