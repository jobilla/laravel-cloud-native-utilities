<?php

namespace Jobilla\CloudNative\Laravel\Logging;

use Monolog\Handler\Handler;
use Monolog\Formatter\JsonFormatter;

class UseJsonFormatting
{
    public function __invoke($logger)
    {
        /** @var Handler $handler */
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(tap(new JsonFormatter)->includeStacktraces());
        }
    }
}
