<?php

namespace App\Services\Crawler;

use App\Models\CrawlRun;
use App\Models\Website;
use App\Services\Crawler\Download\PageContentFetcher;
use App\Services\Crawler\Parsing\HtmlParser;
use App\Services\Crawler\Persistence\CrawlResultPersister;
use App\Services\CrawlAnalysisService;
use App\Services\Crawler\Url\UrlNormalizer;
use App\Services\Crawler\DTO\CrawlOptions;
use App\Services\Analyzer\TechnologyDetectionService;
use App\Services\Crawler\RobotsTxt\RobotsTxtService;
use App\Services\Crawler\Sitemap\SitemapService;
use App\Services\Crawler\Rendering\RenderDecisionService;
use App\Services\Crawler\Enums\ErrorCode;
use App\Services\Crawler\Enums\ErrorSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class CrawlerService
{

    public function __construct(
        private readonly PageContentFetcher $contentFetcher,
        private readonly HtmlParser $parser,
        private readonly CrawlResultPersister $persister,
        private readonly CrawlAnalysisService $crawlAnalysisService,
        private readonly UrlNormalizer $urlNormalizer,
        private readonly TechnologyDetectionService $technologyDetectionService,
        private readonly RobotsTxtService $robotsTxtService,
        private readonly SitemapService $sitemapService,
        private readonly RenderDecisionService $renderDecisionService,
        private readonly ErrorService $errorService,
    ) {
    }

    public function crawl(string $url, ?CrawlOptions $options = null): CrawlRun
    {
        $options ??= new CrawlOptions();
        $normalizedUrl = $this->urlNormalizer->normalizeStartUrl($url);
        $host = parse_url($normalizedUrl, PHP_URL_HOST);

        $website = Website::firstOrCreate(
            ['url' => $normalizedUrl],
            ['host' => $host]
        );

        $crawlRun = CrawlRun::create([
            'website_id' => $website->id,
            'status' => 'running',
            'started_at' => now(),
            'max_pages' => $options->maxPages,
            'max_depth' => $options->maxDepth,
        ]);

        try {
            // Fetch robots.txt before crawling
            $this->robotsTxtService->fetchAndStore($website, $crawlRun);
            
            // Fetch sitemaps (discovers from robots.txt or fallback paths)
            $this->sitemapService->fetchAndStore($website, $crawlRun);

            $queue = [
                ['url' => $normalizedUrl, 'depth' => 0],
            ];

            $visited = [];
            $queued = [$normalizedUrl => true];
            $renderedCount = 0;
            $rendererEnabled = config('services.renderer.enabled', true);
            $maxRenderedPages = config('services.renderer.max_per_crawl', 10);

            while ($queue !== [] && count($visited) < $options->maxPages) {
                $next = array_shift($queue);

                $currentUrl = $next['url'];
                $currentDepth = $next['depth'];

                if (isset($visited[$currentUrl])) {
                    continue;
                }

                $visited[$currentUrl] = true;

                try {
                    // Fetch HTTP content first
                    $downloadedPage = $this->contentFetcher->fetchHttp($currentUrl);
                    
                    // Check for HTTP errors
                    if ($downloadedPage->statusCode >= 400) {
                        $errorCode = $downloadedPage->statusCode >= 500 
                            ? ErrorCode::HTTP_5XX 
                            : ErrorCode::HTTP_4XX;
                        
                        $this->errorService->recordError(
                            crawlRun: $crawlRun,
                            code: $errorCode,
                            source: ErrorSource::HTTP,
                            url: $currentUrl,
                            message: "HTTP {$downloadedPage->statusCode}",
                            context: [
                                'status_code' => $downloadedPage->statusCode,
                                'final_url' => $downloadedPage->finalUrl,
                            ],
                            depth: $currentDepth
                        );

                        // Check if this is a fatal error (start URL)
                        if ($this->errorService->isFatal($errorCode, $currentUrl, $normalizedUrl)) {
                            throw new \RuntimeException("Start URL returned {$downloadedPage->statusCode}");
                        }

                        continue; // Skip this page but continue crawl
                    }

                    // Check for redirect loops or limits
                    if ($downloadedPage->redirectCount >= 10) {
                        $this->errorService->recordError(
                            crawlRun: $crawlRun,
                            code: ErrorCode::REDIRECT_LIMIT_EXCEEDED,
                            source: ErrorSource::HTTP,
                            url: $currentUrl,
                            message: "Redirect limit exceeded (10 redirects)",
                            context: [
                                'redirect_count' => $downloadedPage->redirectCount,
                                'redirect_chain' => $downloadedPage->redirectChain,
                            ],
                            depth: $currentDepth
                        );
                        continue;
                    }
                    
                    $fetchMethod = 'http';
                    $rendererReason = null;

                    // Check if rendering is needed
                    $shouldRender = $rendererEnabled 
                        && $renderedCount < $maxRenderedPages
                        && $this->renderDecisionService->shouldRender($downloadedPage);

                    if ($shouldRender) {
                        $rendererReason = $this->renderDecisionService->getReason($downloadedPage);
                        
                        // Try to render
                        try {
                            $renderedPage = $this->contentFetcher->fetchRendered($currentUrl);
                            
                            if ($renderedPage !== null) {
                                // Successfully rendered - use rendered content
                                $downloadedPage = $renderedPage;
                                $fetchMethod = 'renderer';
                                $renderedCount++;
                            } else {
                                // Renderer returned null - record error and fall back
                                $this->errorService->recordError(
                                    crawlRun: $crawlRun,
                                    code: ErrorCode::RENDERER_FAILED,
                                    source: ErrorSource::RENDERER,
                                    url: $currentUrl,
                                    message: "Renderer returned null, falling back to HTTP content",
                                    depth: $currentDepth
                                );
                            }
                        } catch (ConnectionException $rendererException) {
                            // Renderer connection failed
                            $this->errorService->recordError(
                                crawlRun: $crawlRun,
                                code: ErrorCode::RENDERER_UNAVAILABLE,
                                source: ErrorSource::RENDERER,
                                url: $currentUrl,
                                message: "Renderer unavailable: {$rendererException->getMessage()}",
                                context: ['exception' => get_class($rendererException)],
                                depth: $currentDepth
                            );
                        } catch (\Throwable $rendererException) {
                            // Other renderer errors
                            $this->errorService->recordError(
                                crawlRun: $crawlRun,
                                code: ErrorCode::RENDERER_FAILED,
                                source: ErrorSource::RENDERER,
                                url: $currentUrl,
                                message: "Renderer failed: {$rendererException->getMessage()}",
                                context: ['exception' => get_class($rendererException)],
                                depth: $currentDepth
                            );
                        }
                    }

                    $parsedPage = $this->parser->parse($downloadedPage, $fetchMethod, $rendererReason);

                    // Mark final URL as visited too if it's different (redirect)
                    if ($downloadedPage->finalUrl !== $currentUrl) {
                        $visited[$downloadedPage->finalUrl] = true;
                    }

                    $this->persister->persist(
                        website: $website,
                        crawlRun: $crawlRun,
                        page: $parsedPage,
                        depth: $currentDepth,
                    );

                    if ($currentDepth >= $options->maxDepth) {
                        continue;
                    }

                    foreach ($parsedPage->links as $link) {
                        $targetUrl = $this->urlNormalizer->normalizeLink(
                            href: $link['href'],
                            baseUrl: $currentUrl,
                        );

                        if ($targetUrl === null) {
                            continue;
                        }

                        if (!$this->urlNormalizer->isInternal($targetUrl, $normalizedUrl)) {
                            continue;
                        }

                        if (isset($visited[$targetUrl])) {
                            continue;
                        }

                        if (isset($queued[$targetUrl])) {
                            continue;
                        }

                        $queue[] = [
                            'url' => $targetUrl,
                            'depth' => $currentDepth + 1,
                        ];

                        $queued[$targetUrl] = true;
                    }
                } catch (ConnectionException $exception) {
                    // Connection failed (network unreachable, connection refused, etc.)
                    $errorCode = ErrorCode::CONNECTION_FAILED;
                    
                    // Check for specific DNS errors
                    if (str_contains($exception->getMessage(), 'getaddrinfo') 
                        || str_contains($exception->getMessage(), 'resolve host')
                        || str_contains($exception->getMessage(), 'Could not resolve host')) {
                        $errorCode = ErrorCode::DNS_FAILED;
                    }

                    $this->errorService->recordError(
                        crawlRun: $crawlRun,
                        code: $errorCode,
                        source: ErrorSource::HTTP,
                        url: $currentUrl,
                        message: $exception->getMessage(),
                        context: ['exception' => get_class($exception)],
                        depth: $currentDepth
                    );

                    // Check if this is a fatal error (start URL)
                    if ($this->errorService->isFatal($errorCode, $currentUrl, $normalizedUrl)) {
                        throw $exception;
                    }
                } catch (RequestException $exception) {
                    // HTTP request exception (timeouts, etc.)
                    $errorCode = str_contains($exception->getMessage(), 'timed out') || str_contains($exception->getMessage(), 'timeout')
                        ? ErrorCode::TIMEOUT
                        : ErrorCode::CONNECTION_FAILED;

                    $this->errorService->recordError(
                        crawlRun: $crawlRun,
                        code: $errorCode,
                        source: ErrorSource::HTTP,
                        url: $currentUrl,
                        message: $exception->getMessage(),
                        context: ['exception' => get_class($exception)],
                        depth: $currentDepth
                    );

                    // Check if this is a fatal error (start URL)
                    if ($this->errorService->isFatal($errorCode, $currentUrl, $normalizedUrl)) {
                        throw $exception;
                    }
                } catch (\Throwable $exception) {
                    // Generic error handling
                    $this->errorService->recordError(
                        crawlRun: $crawlRun,
                        code: ErrorCode::UNKNOWN,
                        source: ErrorSource::CRAWLER,
                        url: $currentUrl,
                        message: $exception->getMessage(),
                        context: ['exception' => get_class($exception)],
                        depth: $currentDepth
                    );

                    // Check if this is depth 0 (start URL) - fatal
                    if ($currentDepth === 0) {
                        throw $exception;
                    }
                }
            }

            $crawlRun->update([
                'pages_crawled' => $crawlRun->pages()->count(),
            ]);

            $this->crawlAnalysisService->analyze($crawlRun);

            $this->technologyDetectionService->analyze($crawlRun);

            $crawlRun->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

        } catch (\Throwable $exception) {
            $crawlRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $crawlRun->fresh();
    }
}