<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AiAnalysisService;
use App\Services\GoogleGeminiService;
use Illuminate\Http\JsonResponse;

class AiHealthController extends Controller
{
    public function __invoke(
        AiAnalysisService $aiAnalysis,
        GoogleGeminiService $gemini,
    ): JsonResponse {
        $provider = $aiAnalysis->providerName();

        if ($provider !== 'gemini') {
            return response()->json([
                'status'  => (string) config('openrouter.api_key', '') !== '' ? 'ok' : 'misconfigured',
            ]);
        }

        $health = $gemini->healthCheck();

        return response()->json([
            'status' => ($health['available'] ?? false) ? 'ok' : 'degraded',
        ]);
    }
}
