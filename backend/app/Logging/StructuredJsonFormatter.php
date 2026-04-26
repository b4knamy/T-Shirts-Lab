<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

/**
 * Structured JSON formatter that enriches every log entry with
 * metadata fields that log aggregators (Loki, Elasticsearch, Datadog)
 * use for filtering, alerting, and dashboards:
 *
 *   - channel      → domain filter (auth, payment, order…)
 *   - environment  → environment filter (production, staging…)
 *   - app          → service name in a multi-service cluster
 *   - pod          → which Kubernetes pod emitted the log
 *   - node         → which Kubernetes node
 *   - namespace    → Kubernetes namespace
 *   - version      → app release version (canary tracking)
 *   - request_id   → correlate all logs from a single HTTP request
 */
class StructuredJsonFormatter extends JsonFormatter
{
    public function __construct()
    {
        parent::__construct(
            batchMode: self::BATCH_MODE_NEWLINES,
            appendNewline: true,
            ignoreEmptyContextAndExtra: false,
            includeStacktraces: false,  // true causes OOM — exception objects contain circular refs
        );
    }

    public function format(LogRecord $record): string
    {
        $requestId = null;

        try {
            $requestId = request()->attributes?->get('request_id');
        } catch (\Throwable) {
            // Request not available (CLI, queue workers, etc.)
        }

        $record = $record->with(extra: array_merge($record->extra, [
            // App identity
            'app' => config('app.name', 'tshirtslab'),
            'environment' => config('app.env', 'production'),
            'version' => config('app.version', 'unknown'),

            // Kubernetes identity (injected via Downward API in pod spec)
            'pod' => env('K8S_POD_NAME', gethostname()),
            'node' => env('K8S_NODE_NAME', 'local'),
            'namespace' => env('K8S_NAMESPACE', 'default'),

            // Request correlation
            'request_id' => $requestId,
        ]));

        return parent::format($record);
    }
}
