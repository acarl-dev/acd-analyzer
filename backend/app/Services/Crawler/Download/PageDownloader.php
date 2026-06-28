<?php

namespace App\Services\Crawler\Download;

use App\Services\Crawler\DTO\DownloadedPage;
use Illuminate\Support\Facades\Http;

class PageDownloader
{
    public function download(string $url): DownloadedPage
    {
        $started = microtime(true);

        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Alan Carl Digital Analyzer/0.1',
            ])
            ->get($url);

        $responseTime = (int) ((microtime(true) - $started) * 1000);

        return new DownloadedPage(
            url: $url,
            statusCode: $response->status(),
            html: $response->body(),
            responseTimeMs: $responseTime,
        );
    }
}