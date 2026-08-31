<?php

namespace App\Http\Controllers\Api;

use App\Models\CrawlRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlRunErrorsController
{
    public function __invoke(Request $request, CrawlRun $crawlRun): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 200);
        $severity = $request->query('severity');
        $source = $request->query('source');
        $code = $request->query('code');

        $query = $crawlRun->errors()->orderBy('occurred_at', 'desc');

        if ($severity) {
            $query->where('severity', $severity);
        }

        if ($source) {
            $query->where('source', $source);
        }

        if ($code) {
            $query->where('code', $code);
        }

        $errors = $query->paginate($perPage);

        return response()->json([
            'data' => $errors->map(fn ($error) => [
                'id' => $error->id,
                'code' => $error->code,
                'severity' => $error->severity,
                'source' => $error->source,
                'url' => $error->url,
                'message' => $error->message,
                'context' => $error->context,
                'depth' => $error->depth,
                'occurredAt' => $error->occurred_at?->toISOString(),
                'createdAt' => $error->created_at?->toISOString(),
            ]),
            'pagination' => [
                'currentPage' => $errors->currentPage(),
                'perPage' => $errors->perPage(),
                'total' => $errors->total(),
                'lastPage' => $errors->lastPage(),
            ],
        ]);
    }
}
