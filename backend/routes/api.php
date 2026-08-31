<?php

use App\Http\Controllers\Api\CrawlController;
use App\Http\Controllers\Api\CrawlResultsController;
use App\Http\Controllers\Api\CrawlRunOverviewController;
use App\Http\Controllers\Api\CrawlRunPagesController;
use App\Http\Controllers\Api\CrawlRunLinksController;
use App\Http\Controllers\Api\CrawlRunRedirectsController;
use App\Http\Controllers\Api\CrawlRunRobotsTxtController;
use App\Http\Controllers\Api\CrawlRunSitemapsController;
use App\Http\Controllers\Api\CrawlRunCanonicalsController;
use App\Http\Controllers\Api\CrawlRunIssuesController;
use App\Http\Controllers\Api\DashboardSummaryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/crawl', [CrawlController::class, 'store']);
Route::get('/crawl-runs', [CrawlController::class, 'index']);
Route::get('/crawl-runs/{crawlRun}/results', [CrawlResultsController::class, 'show']);

// New M1.5.1 endpoints
Route::get('/crawl-runs/{crawlRun}/overview', [CrawlRunOverviewController::class, 'show']);
Route::get('/crawl-runs/{crawlRun}/pages', [CrawlRunPagesController::class, 'index']);
Route::get('/crawl-runs/{crawlRun}/pages/{pageId}', [CrawlRunPagesController::class, 'show']);
Route::get('/crawl-runs/{crawlRun}/links', [CrawlRunLinksController::class, 'index']);
Route::get('/crawl-runs/{crawlRun}/redirects', [CrawlRunRedirectsController::class, 'index']);
Route::get('/crawl-runs/{crawlRun}/robots-txt', [CrawlRunRobotsTxtController::class, 'show']);
Route::get('/crawl-runs/{crawlRun}/sitemaps', [CrawlRunSitemapsController::class, 'index']);
Route::get('/crawl-runs/{crawlRun}/sitemaps/{sitemapId}', [CrawlRunSitemapsController::class, 'show']);
Route::get('/crawl-runs/{crawlRun}/canonicals', [CrawlRunCanonicalsController::class, 'index']);
Route::get('/crawl-runs/{crawlRun}/issues', [CrawlRunIssuesController::class, 'index']);

Route::get('/dashboard/summary', [DashboardSummaryController::class, 'show']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');