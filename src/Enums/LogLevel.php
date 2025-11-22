<?php

declare(strict_types=1);

namespace LogWard\SDK\Enums;

/**
 * Log severity levels
 */
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARN = 'warn';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}
