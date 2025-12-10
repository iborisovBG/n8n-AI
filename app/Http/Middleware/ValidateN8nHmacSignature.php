<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateN8nHmacSignature
{
    /**
     * Handle an incoming request with HMAC signature validation.
     *
     * This provides enhanced security over simple API key validation by using
     * HMAC-SHA256 to sign the request payload, preventing replay attacks.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.n8n.webhook_secret');

        // If webhook secret is not configured, fall back to API key validation
        if (! $secret) {
            Log::info('HMAC secret not configured, skipping HMAC validation');

            return $next($request);
        }

        $providedSignature = $request->header('X-N8N-Signature');

        if (! $providedSignature) {
            Log::warning('HMAC signature missing from n8n webhook', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Missing HMAC signature',
            ], 401);
        }

        // Get raw request body for signature verification
        $payload = $request->getContent();

        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Use timing-safe comparison to prevent timing attacks
        if (! hash_equals($expectedSignature, $providedSignature)) {
            Log::warning('Invalid HMAC signature from n8n webhook', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'provided' => substr($providedSignature, 0, 8).'...',
                'expected' => substr($expectedSignature, 0, 8).'...',
            ]);

            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Invalid HMAC signature',
            ], 401);
        }

        Log::info('HMAC signature validated successfully', [
            'path' => $request->path(),
        ]);

        return $next($request);
    }
}
