<?php

namespace App\Logging\Loggers;

use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseLogger
{
    abstract protected function channel(): string;

    public function info(string $title, array $context = []): void
    {
        $this->log('info', $title, $context);
    }

    public function warning(string $title, array $context = []): void
    {
        $this->log('warning', $title, $context);
    }

    public function error(string $title, array $context = []): void
    {
        $this->log('error', $title, $context);
    }

    public function debug(string $title, array $context = []): void
    {
        $this->log('debug', $title, $context);
    }

    public function notice(string $title, array $context = []): void
    {
        $this->log('notice', $title, $context);
    }

    public function critical(string $title, array $context = []): void
    {
        $this->log('critical', $title, $context);
    }

    public function alert(string $title, array $context = []): void
    {
        $this->log('alert', $title, $context);
    }

    public function emergency(string $title, array $context = []): void
    {
        $this->log('emergency', $title, $context);
    }

    public function exception(string $title, Throwable $e, array $context = []): void
    {
        $this->error($title, array_merge([
            'exception_class' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $context));
    }

    protected function log(string $level, string $title, array $context = []): void
    {
        $context = array_merge($this->withContext(), $context);
        Log::channel($this->channel())->{$level}($title, $context);
    }

    protected function withContext(): array
    {
        if (app()->runningInConsole()) {
            return [];
        }

        $request = request();

        try {
            $userId = $request->user()?->id;
        } catch (Throwable) {
            $userId = null;
        }

        return [
            'request_id' => $request->attributes->get('request_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'authenticated_user_id' => $userId,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ];
    }
}
