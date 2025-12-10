<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateN8nSignature
{
    /**
     * Handle an incoming request with API key validation.
     *
     * This middleware validates the X-N8N-API-KEY header against the configured API key.
     * It serves as a fallback when HMAC signature validation is not configured.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('services.n8n.api_key');

        // If API key is not configured, skip validation
        if (! $apiKey) {
            Log::warning('N8N API key not configured, skipping API key validation');
            return $next($request);
        }

        $providedApiKey = $request->header('X-N8N-API-KEY');

        if (! $providedApiKey) {
            Log::warning('API key missing from n8n webhook', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Missing API key',
            ], 401);
        }

        // Use timing-safe comparison to prevent timing attacks
        if (! hash_equals($apiKey, $providedApiKey)) {
            Log::warning('Invalid API key from n8n webhook', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Invalid API key',
            ], 401);
        }

        Log::info('API key validated successfully', [
            'path' => $request->path(),
        ]);

        return $next($request);
    }
}

