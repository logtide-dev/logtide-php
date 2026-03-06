<?php

declare(strict_types=1);

namespace LogTide\Laravel;

use LogTide\Monolog\LogtideHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogChannel
{
    public function __invoke(array $config): LoggerInterface
    {
        $handler = new LogtideHandler(
            level: $config['level'] ?? 'debug',
            bubble: $config['bubble'] ?? true,
        );

        return new Logger('logtide', [$handler]);
    }
}
