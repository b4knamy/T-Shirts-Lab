<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\FormattableHandlerInterface;

/**
 * Laravel log "tap" — attaches StructuredJsonFormatter to any channel.
 *
 * Usage in config/logging.php:
 *   'tap' => [StructuredJsonTap::class],
 */
class StructuredJsonTap
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new StructuredJsonFormatter);
            }
        }
    }
}
