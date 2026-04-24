<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureJwtPasswordVersionIsValid;
use App\Http\Middleware\JwtAuthenticateMiddleware;
use App\Http\Middleware\RequestLogMiddleware;
use App\Http\Middleware\SecurityLogMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuthenticateMiddleware::class,
            'jwt.password' => EnsureJwtPasswordVersionIsValid::class,
            'admin' => AdminMiddleware::class,
        ]);

        if (env('APP_ENV') !== 'testing') {
            // Request/security logging is useful in runtime environments but
            // adds unnecessary I/O and JSON formatting overhead in the test suite.
            $middleware->api(prepend: [
                RequestLogMiddleware::class,
                SecurityLogMiddleware::class,
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Don't report expected business-logic exceptions (they're handled at the controller layer)
        $exceptions->dontReport([
            \InvalidArgumentException::class,
        ]);

        $exceptions->report(function (Throwable $e) {
            // NOTE: Do NOT include the raw exception object or full trace string in context.
            // LineFormatter (used by the `daily` channel) json_encodes the entire context
            // array, and a full trace for a deep Laravel call stack can be megabytes,
            // causing OOM (attempted allocation of 100MB+) inside json_encode.
            $traceLines = explode("\n", $e->getTraceAsString());
            $context = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace_summary' => implode("\n", array_slice($traceLines, 0, 10)),
            ];

            // Add request context when available
            if (app()->has('request')) {
                $request = request();
                $context['url'] = $request->fullUrl();
                $context['method'] = $request->method();
                $context['ip'] = $request->ip();
                $context['request_id'] = $request->attributes?->get('request_id');
            }

            // Add user context when available
            try {
                $user = app('auth')->user();
                if ($user) {
                    $context['user_id'] = $user->id;
                    $context['user_email'] = $user->email;
                }
            } catch (Throwable) {
                // Auth not available
            }

            Log::error('Application Error', $context);

            // Return false to stop Laravel's default reporter from also running.
            // The default reporter passes the raw exception object to Monolog,
            // which causes OOM when json_encode walks circular object references.
            return false;
        });

        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
