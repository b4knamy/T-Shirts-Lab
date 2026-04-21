<?php

namespace App\Http\Middleware;

use App\Logging\Loggers\SecurityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityLogMiddleware
{
    public function __construct(private SecurityLogger $securityLogger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $statusCode = $response->getStatusCode();

        if ($statusCode === 403) {
            $this->securityLogger->warning('security.access.forbidden', array_filter([
                'message' => $this->extractMessage($response),
            ]));
        }

        if ($statusCode === 429) {
            $this->securityLogger->warning('security.rate_limit.exceeded');
        }

        return $response;
    }

    private function extractMessage(Response $response): ?string
    {
        $content = $response->getContent();

        if (! is_string($content)) {
            return null;
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded['message'] ?? null;
    }
}
