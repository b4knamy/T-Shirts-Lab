<?php

use App\Logging\StructuredJsonFormatter;
use App\Logging\StructuredJsonTap;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Environments:
    |   Local dev  → LOG_STACK=stdout,daily  (JSON on terminal + daily files)
    |   Kubernetes → LOG_STACK=stdout        (JSON only — Promtail/Fluentd collects)
    |   CI/Tests   → LOG_STACK=daily         (files only, no stdout noise)
    |
    | Domain channels (auth, security, payment…) all route through the
    | default stack, so a single LOG_STACK change controls everything.
    | In Loki/ELK you filter by the "channel" JSON field.
    |
    */

    'channels' => [

        /*
        |----------------------------------------------------------------------
        | Stack — the entry point. Fans out to the channels listed in LOG_STACK.
        |----------------------------------------------------------------------
        */
        'stack' => [
            'driver' => 'stack',
            'name' => 'app',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | stdout — Kubernetes-native channel
        |----------------------------------------------------------------------
        | Writes one JSON object per line to php://stdout.
        | `kubectl logs`, Promtail, Fluentd, Datadog Agent all read stdout.
        | StructuredJsonFormatter enriches each line with: app, environment,
        | version, pod, node, namespace, request_id.
        |----------------------------------------------------------------------
        */
        'stdout' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => StructuredJsonFormatter::class,
            'with' => [
                'stream' => 'php://stdout',
            ],
            'level' => env('LOG_LEVEL', 'debug'),
            'tap' => [StructuredJsonTap::class],
        ],

        /*
        |----------------------------------------------------------------------
        | stderr — critical/emergency only (Kubernetes marks these as errors)
        |----------------------------------------------------------------------
        */
        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => StructuredJsonFormatter::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
            'level' => 'critical',
            'tap' => [StructuredJsonTap::class],
        ],

        /*
        |----------------------------------------------------------------------
        | File-based channels — used in local dev as fallback / convenience
        |----------------------------------------------------------------------
        */
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
            'formatter' => StructuredJsonFormatter::class,
            'tap' => [StructuredJsonTap::class],
        ],

        /*
        |----------------------------------------------------------------------
        | Domain-specific channels
        |----------------------------------------------------------------------
        | Each domain channel fans out to the same LOG_STACK targets as the
        | default stack. The Monolog "channel" field in every JSON line
        | identifies the domain (auth, payment, security…), so Loki/ELK
        | can filter with:  {channel="auth"} or {channel="payment"}
        | without needing separate files.
        |----------------------------------------------------------------------
        */
        'auth' => [
            'driver' => 'stack',
            'name' => 'auth',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        'security' => [
            'driver' => 'stack',
            'name' => 'security',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        'payment' => [
            'driver' => 'stack',
            'name' => 'payment',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        'request' => [
            'driver' => 'stack',
            'name' => 'request',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        'webhook' => [
            'driver' => 'stack',
            'name' => 'webhook',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        'order' => [
            'driver' => 'stack',
            'name' => 'order',
            'channels' => explode(',', (string) env('LOG_STACK', 'stdout,daily')),
            'ignore_exceptions' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Third-party integrations (optional)
        |----------------------------------------------------------------------
        */
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
