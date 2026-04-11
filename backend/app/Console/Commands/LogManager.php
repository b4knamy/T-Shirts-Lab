<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogManager extends Command
{
    protected $signature = 'logs:manage
                            {action=status : Action to perform: status, clear, tail, info}
                            {--channel= : Specific log channel (auth, security, payment, request, webhook, order, laravel)}
                            {--lines=50 : Number of lines to show for tail action}';

    protected $description = 'Manage application log files (status, clear, tail, info)';

    private const LOG_FILES = [
        'laravel' => 'logs/laravel*.log',
        'auth' => 'logs/auth*.log',
        'security' => 'logs/security*.log',
        'payment' => 'logs/payment*.log',
        'request' => 'logs/request*.log',
        'webhook' => 'logs/webhook*.log',
        'order' => 'logs/order*.log',
    ];

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'status' => $this->showStatus(),
            'clear' => $this->clearLogs(),
            'tail' => $this->tailLog(),
            'info' => $this->showInfo(),
            default => $this->invalidAction($action),
        };
    }

    private function showInfo(): int
    {
        $stack = config('logging.channels.stack.channels', []);
        $level = config('logging.channels.stdout.level', env('LOG_LEVEL', 'debug'));

        $this->info('🔧 Logging Configuration');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['Default channel', config('logging.default')],
            ['Stack targets', implode(', ', $stack)],
            ['Log level', $level],
            ['App version', config('app.version', 'unknown')],
            ['Environment', config('app.env')],
            ['K8s pod', env('K8S_POD_NAME', '(not set — local dev)')],
            ['K8s node', env('K8S_NODE_NAME', '(not set)')],
            ['K8s namespace', env('K8S_NAMESPACE', '(not set)')],
        ]);

        $hasStdout = in_array('stdout', $stack);
        $hasDaily = in_array('daily', $stack);

        $this->newLine();
        if ($hasStdout && $hasDaily) {
            $this->info('📡 Mode: Hybrid (stdout JSON + daily files)');
        } elseif ($hasStdout) {
            $this->info('📡 Mode: Kubernetes (stdout JSON only)');
        } elseif ($hasDaily) {
            $this->info('📡 Mode: File-only (daily rotation)');
        } else {
            $this->warn('⚠️  Mode: Custom stack — '.implode(', ', $stack));
        }

        return Command::SUCCESS;
    }

    private function showStatus(): int
    {
        $this->info('📊 Log Files Status');
        $this->newLine();

        $rows = [];
        $totalSize = 0;

        foreach (self::LOG_FILES as $channel => $pattern) {
            $files = glob(storage_path($pattern));
            $size = 0;
            $count = count($files);
            $latest = null;

            foreach ($files as $file) {
                $fileSize = filesize($file);
                $size += $fileSize;
                $modified = filemtime($file);
                if ($latest === null || $modified > $latest) {
                    $latest = $modified;
                }
            }

            $totalSize += $size;

            $rows[] = [
                $channel,
                $count,
                $this->formatBytes($size),
                $latest ? date('Y-m-d H:i:s', $latest) : 'N/A',
            ];
        }

        $this->table(
            ['Channel', 'Files', 'Total Size', 'Last Modified'],
            $rows
        );

        $this->info("Total log size: {$this->formatBytes($totalSize)}");

        return Command::SUCCESS;
    }

    private function clearLogs(): int
    {
        $channel = $this->option('channel');

        if ($channel && ! isset(self::LOG_FILES[$channel])) {
            $this->error("Unknown channel: {$channel}");
            $this->info('Available: '.implode(', ', array_keys(self::LOG_FILES)));

            return Command::FAILURE;
        }

        $channels = $channel ? [$channel => self::LOG_FILES[$channel]] : self::LOG_FILES;

        if (! $channel && ! $this->confirm('Clear ALL log files?', false)) {
            $this->info('Aborted.');

            return Command::SUCCESS;
        }

        foreach ($channels as $name => $pattern) {
            $files = glob(storage_path($pattern));
            $count = 0;

            foreach ($files as $file) {
                File::delete($file);
                $count++;
            }

            if ($count > 0) {
                $this->info("✅ Cleared {$count} file(s) for [{$name}]");
            }
        }

        return Command::SUCCESS;
    }

    private function tailLog(): int
    {
        $channel = $this->option('channel') ?? 'laravel';
        $lines = (int) $this->option('lines');

        if (! isset(self::LOG_FILES[$channel])) {
            $this->error("Unknown channel: {$channel}");
            $this->info('Available: '.implode(', ', array_keys(self::LOG_FILES)));

            return Command::FAILURE;
        }

        $files = glob(storage_path(self::LOG_FILES[$channel]));

        if (empty($files)) {
            $this->warn("No log files found for [{$channel}]");

            return Command::SUCCESS;
        }

        // Get the most recent file
        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
        $latestFile = $files[0];

        $this->info("📄 Tailing: {$latestFile} (last {$lines} lines)");
        $this->newLine();

        $content = $this->tailFile($latestFile, $lines);
        $this->line($content);

        return Command::SUCCESS;
    }

    private function tailFile(string $file, int $lines): string
    {
        $allLines = file($file, FILE_IGNORE_NEW_LINES);

        if ($allLines === false) {
            return '';
        }

        $slice = array_slice($allLines, -$lines);

        return implode(PHP_EOL, $slice);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2).' '.$units[$i];
    }

    private function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->info('Available actions: status, clear, tail, info');

        return Command::FAILURE;
    }
}
