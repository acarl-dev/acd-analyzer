<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummaryService;
use Illuminate\Http\JsonResponse;

final class DashboardSummaryController extends Controller
{
    public function __construct(
        private readonly DashboardSummaryService $dashboardSummaryService,
    ) {
    }

    public function show(): JsonResponse
    {
        return response()->json(
            $this->dashboardSummaryService->build()
        );
    }
}