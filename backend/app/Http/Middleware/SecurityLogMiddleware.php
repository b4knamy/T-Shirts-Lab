<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityLogMiddleware
{
    /**
     * Log security-relevant events: auth failures, forbidden access, rate limits.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $statusCode = $response->getStatusCode();

        if ($statusCode === 401) {
            $this->logAuthFailure($request, $response);
        }

        if ($statusCode === 403) {
            $this->logForbidden($request, $response);
        }

        if ($statusCode === 429) {
            $this->logRateLimited($request);
        }

        return $response;
    }

    private function logAuthFailure(Request $request, Response $response): void
    {
        Log::channel('security')->warning('Authentication failure', [
            'request_id' => $request->attributes->get('request_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'message' => $this->extractMessage($response),
        ]);
    }

    private function logForbidden(Request $request, Response $response): void
    {
        Log::channel('security')->warning('Forbidden access attempt', [
            'request_id' => $request->attributes->get('request_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'message' => $this->extractMessage($response),
        ]);
    }

    private function logRateLimited(Request $request): void
    {
        Log::channel('security')->warning('Rate limit exceeded', [
            'request_id' => $request->attributes->get('request_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);
    }

    private function extractMessage(Response $response): ?string
    {
        try {
            $content = json_decode($response->getContent(), true);

            return $content['message'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
