<?php

namespace App\Logging\Loggers;

class RequestLogger extends BaseLogger
{
    protected function channel(): string
    {
        return 'request';
    }
}
