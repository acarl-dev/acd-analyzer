<?php

use App\Http\Controllers\Api\CrawlController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/crawl', [CrawlController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
