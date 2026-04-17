<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLogMiddleware
{
    /**
     * Paths that should never have their bodies logged (contain sensitive data).
     */
    private const SENSITIVE_PATHS = [
        'api/v1/auth/login',
        'api/v1/auth/register',
        'api/v1/auth/refresh',
        'api/webhooks/stripe',
    ];

    /**
     * Request body fields that should be redacted from logs.
     */
    private const REDACTED_FIELDS = [
        'password',
        'password_confirmation',
        'password_hash',
        'refresh_token',
        'access_token',
        'stripe_secret',
        'card_number',
        'cvv',
        'cvc',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();

        // Attach request ID for correlation across logs
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $this->logRequest($request, $response, $startTime, $requestId);

        return $response;
    }

    private function logRequest(Request $request, Response $response, float $startTime, string $requestId): void
    {
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode();

        $userId = $this->resolveUserId($request);

        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'status' => $statusCode,
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $userId,
        ];

        // Only log request body for non-sensitive paths
        if (! $this->isSensitivePath($request->path())) {
            $body = $this->sanitizeBody($request->all());
            if (! empty($body)) {
                $context['request_body'] = $body;
            }
        }

        // Determine log level based on response status
        $level = match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            default => 'info',
        };

        $message = sprintf(
            '%s %s %d %sms',
            $request->method(),
            $request->path(),
            $statusCode,
            $durationMs
        );

        Log::channel('request')->{$level}($message, $context);

        // Also log slow requests (> 2 seconds) to the main log
        if ($durationMs > 2000) {
            Log::warning('Slow request detected', $context);
        }
    }

    private function resolveUserId(Request $request): ?string
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return $user?->id;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isSensitivePath(string $path): bool
    {
        foreach (self::SENSITIVE_PATHS as $sensitivePath) {
            if (str_starts_with($path, $sensitivePath)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeBody(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), self::REDACTED_FIELDS, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeBody($value);
            }
        }

        return $data;
    }
}
