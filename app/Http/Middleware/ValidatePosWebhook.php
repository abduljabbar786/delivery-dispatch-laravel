<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePosWebhook
{
    /**
     * Handle an incoming request.
     *
     * Validates the API key from the POS system
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-POS-API-Key');

        // Check if API key is provided
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required. Please provide X-POS-API-Key header.',
            ], 401);
        }

        // Validate API key
        $validApiKey = env('POS_WEBHOOK_API_KEY');

        if ($apiKey !== $validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.',
            ], 403);
        }

        return $next($request);
    }
}
