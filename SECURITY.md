# Security Features

This document outlines the security features implemented in the n8n-AI application.

## API Authentication

### Sanctum Authentication for API Endpoints

All API endpoints (except health check and webhook callback) are protected with Laravel Sanctum authentication:

```php
// Protected endpoints require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/', [AdScriptController::class, 'store']);
    Route::get('/', [AdScriptController::class, 'index']);
    Route::get('{id}', [AdScriptController::class, 'show']);
});
```

**Usage:**
```bash
# Create API token for user
$token = $user->createToken('api-token')->plainTextToken;

# Use token in requests
curl -H "Authorization: Bearer $token" \
     http://localhost:8000/api/ad-scripts
```

## Webhook Security

### Two-Layer Validation

The n8n webhook callback endpoint uses two layers of security:

1. **HMAC Signature Validation** (Primary)
2. **API Key Validation** (Fallback)

#### HMAC Signature Validation

HMAC (Hash-based Message Authentication Code) provides cryptographic verification that requests come from n8n and haven't been tampered with.

**Configuration:**
```env
N8N_WEBHOOK_SECRET=your-secret-key-here
```

**How it works:**
1. n8n calculates HMAC-SHA256 of the request body using the shared secret
2. n8n sends the signature in the `X-N8N-Signature` header
3. Laravel recalculates the signature and compares using timing-safe comparison
4. Request is rejected if signatures don't match

**n8n Configuration:**
In your n8n workflow, add an HTTP Request node to send results back to Laravel:

```json
{
  "url": "{{ $json.callback_url }}",
  "method": "POST",
  "headers": {
    "X-N8N-Signature": "{{ $crypto.createHmac('sha256', 'YOUR_SECRET').update($json.body).digest('hex') }}"
  },
  "body": {
    "new_script": "{{ $json.new_script }}",
    "analysis": "{{ $json.analysis }}"
  }
}
```

**Security Benefits:**
- Prevents replay attacks
- Ensures message integrity
- Cryptographically secure
- Timing-attack resistant

#### API Key Validation (Fallback)

If HMAC secret is not configured, the system falls back to simple API key validation.

**Configuration:**
```env
N8N_API_KEY=your-api-key-here
```

**How it works:**
1. n8n sends API key in `X-N8N-API-KEY` header
2. Laravel validates against configured key
3. Request is rejected if keys don't match

**n8n Configuration:**
```json
{
  "url": "{{ $json.callback_url }}",
  "method": "POST",
  "headers": {
    "X-N8N-API-KEY": "your-api-key-here"
  }
}
```

### Validation Flow

```
Incoming Request
      ↓
[HMAC Validation]
      ↓
   Secret configured?
      ↓
    Yes → Validate HMAC signature
      ↓         ↓
    Valid?    Invalid?
      ↓         ↓
   [Pass]   [401 Unauthorized]
      ↓
[API Key Validation]
      ↓
   Key configured?
      ↓
    Yes → Validate API key
      ↓         ↓
    Valid?    Invalid?
      ↓         ↓
   [Pass]   [401 Unauthorized]
      ↓
[Process Request]
```

## Rate Limiting

### API Rate Limiting

API endpoints are protected with configurable rate limiting:

**Configuration:**
```env
N8N_RATE_LIMIT_MAX_ATTEMPTS=60
N8N_RATE_LIMIT_DECAY_MINUTES=1
```

**Default:** 60 requests per minute per IP address

**Implementation:**
```php
class ThrottleAdScriptRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = config('services.n8n.rate_limit.max_attempts', 60);
        $decayMinutes = config('services.n8n.rate_limit.decay_minutes', 1);

        $key = 'ad-script-request:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests',
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return $next($request);
    }
}
```

## Webhook Retry Mechanism

### Exponential Backoff

Failed webhook requests to n8n are automatically retried with exponential backoff:

**Configuration:**
```env
N8N_RETRY_MAX_ATTEMPTS=3
N8N_RETRY_DELAY_SECONDS=5
N8N_RETRY_BACKOFF_MULTIPLIER=2
```

**Retry Schedule:**
- Attempt 1: Immediate
- Attempt 2: After 5 seconds (5 * 2^0)
- Attempt 3: After 10 seconds (5 * 2^1)
- Attempt 4: After 20 seconds (5 * 2^2)

**Implementation:**
```php
class SendAdScriptToN8n implements ShouldQueue
{
    public function backoff(): array
    {
        $baseDelay = config('services.n8n.retry.delay_seconds', 5);
        $multiplier = config('services.n8n.retry.backoff_multiplier', 2);

        // Calculate exponential backoff delays
        $backoffs = [];
        for ($i = 1; $i < $this->tries; $i++) {
            $backoffs[] = $baseDelay * pow($multiplier, $i - 1);
        }

        return $backoffs;
    }
}
```

**Features:**
- Automatic retry on connection failures
- Exponential backoff to prevent overwhelming the service
- Configurable max attempts
- Detailed logging of each attempt
- Slack notifications on final failure

## Security Best Practices

### Production Deployment

1. **Use HMAC Validation:**
   - Always configure `N8N_WEBHOOK_SECRET` in production
   - Use a strong, random secret (at least 32 characters)
   - Rotate secrets periodically

2. **Secure API Keys:**
   - Use environment variables, never commit secrets
   - Use different keys for different environments
   - Rotate keys regularly

3. **Enable HTTPS:**
   - Use SSL/TLS for all API communications
   - Configure n8n with HTTPS
   - Use valid SSL certificates

4. **Rate Limiting:**
   - Adjust rate limits based on your usage patterns
   - Monitor for abuse
   - Consider IP whitelisting for known clients

5. **Monitoring:**
   - Review logs regularly
   - Set up alerts for authentication failures
   - Monitor Slack notifications for webhook failures

### Generating Secure Secrets

**HMAC Secret:**
```bash
php -r "echo bin2hex(random_bytes(32));"
# or
openssl rand -hex 32
```

**API Key:**
```bash
php -r "echo bin2hex(random_bytes(16));"
# or
openssl rand -hex 16
```

## Testing Security

### Test HMAC Validation

```bash
# Generate test signature
SECRET="your-secret-key"
PAYLOAD='{"new_script":"test","analysis":"test"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Test valid signature
curl -X POST http://localhost:8000/api/ad-scripts/1/result \
  -H "Content-Type: application/json" \
  -H "X-N8N-Signature: $SIGNATURE" \
  -d "$PAYLOAD"

# Test invalid signature (should return 401)
curl -X POST http://localhost:8000/api/ad-scripts/1/result \
  -H "Content-Type: application/json" \
  -H "X-N8N-Signature: invalid-signature" \
  -d "$PAYLOAD"
```

### Test API Key Validation

```bash
# Test valid API key
curl -X POST http://localhost:8000/api/ad-scripts/1/result \
  -H "Content-Type: application/json" \
  -H "X-N8N-API-KEY: your-api-key" \
  -d '{"new_script":"test","analysis":"test"}'

# Test invalid API key (should return 401)
curl -X POST http://localhost:8000/api/ad-scripts/1/result \
  -H "Content-Type: application/json" \
  -H "X-N8N-API-KEY: wrong-key" \
  -d '{"new_script":"test","analysis":"test"}'
```

### Test Rate Limiting

```bash
# Send 61 requests rapidly (should hit rate limit)
for i in {1..61}; do
  curl -H "Authorization: Bearer $TOKEN" \
       http://localhost:8000/api/ad-scripts
done
```

## Troubleshooting

### HMAC Validation Failures

**Check logs:**
```bash
tail -f storage/logs/laravel.log | grep HMAC
```

**Common issues:**
- Body encoding mismatch (ensure UTF-8)
- Trailing whitespace in payload
- Secret mismatch between Laravel and n8n
- Clock skew (not applicable for HMAC but good to check)

**Debug mode:**
```php
// Temporarily log expected vs provided signatures
Log::debug('HMAC Debug', [
    'expected' => $expectedSignature,
    'provided' => $providedSignature,
    'payload' => $payload,
]);
```

### API Key Validation Failures

**Check configuration:**
```bash
php artisan config:clear
php artisan config:cache
```

**Verify environment variables:**
```bash
php artisan tinker
>>> config('services.n8n.api_key')
```

### Rate Limit Issues

**Clear rate limiter:**
```bash
php artisan cache:clear
```

**Check current limits:**
```bash
php artisan tinker
>>> RateLimiter::attempts('ad-script-request:127.0.0.1')
```



## References

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [HMAC-SHA256 RFC](https://tools.ietf.org/html/rfc2104)
- [OWASP API Security](https://owasp.org/www-project-api-security/)
- [n8n Security Best Practices](https://docs.n8n.io/hosting/security/)
