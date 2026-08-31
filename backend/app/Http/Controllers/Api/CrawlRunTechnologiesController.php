<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;

class CrawlRunTechnologiesController extends Controller
{
    public function __invoke(CrawlRun $crawlRun): JsonResponse
    {
        $technologies = $crawlRun->detectedTechnologies()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($tech) {
                // Handle both old string format and new array format for evidence
                $evidence = $tech->evidence;
                if (is_string($evidence)) {
                    // Old format: convert string to array format
                    $evidence = [['source' => 'legacy', 'value' => $evidence]];
                } elseif (!is_array($evidence)) {
                    $evidence = [];
                }
                
                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'slug' => $tech->slug,
                    'category' => $tech->category,
                    'confidence' => $tech->confidence,
                    'version' => $tech->version,
                    'evidence' => $evidence,
                    'sources' => $tech->sources ?? [],
                    'detectedOnPages' => $tech->detected_on_pages ?? 0,
                ];
            });

        return response()->json([
            'data' => $technologies,
        ]);
    }
}
