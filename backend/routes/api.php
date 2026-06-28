<?php

use App\Http\Controllers\Api\CrawlController;
use App\Http\Controllers\Api\CrawlResultsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/crawl', [CrawlController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/crawl-runs/{crawlRun}/results', [CrawlResultsController::class, 'show']);