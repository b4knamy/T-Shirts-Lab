<?php

namespace App\Logging;

use App\Logging\Loggers\AuthLogger;

class Logger
{
    static public function auth(): AuthLogger
    {
        return app(AuthLogger::class);
    }
}
