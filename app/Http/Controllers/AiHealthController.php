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
                'provider'   => $provider,
                'configured' => (string) config('openrouter.api_key', '') !== '',
                'model'      => config('openrouter.default_model') ?: 'openrouter chain',
                'available'  => null,
                'status'     => (string) config('openrouter.api_key', '') !== '' ? 'configured' : 'misconfigured',
            ]);
        }

        $health = $gemini->healthCheck();

        return response()->json($health);
    }
}
