<?php

namespace App\Logging;

use App\Logging\JsonFormatter;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

class ApplogFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $processor = new IntrospectionProcessor(Logger::DEBUG, ['Illuminate']);
            $handler->setFormatter(new JsonFormatter())->pushProcessor($processor);
        }
    }
}
