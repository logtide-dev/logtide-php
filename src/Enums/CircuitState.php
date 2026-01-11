<?php

declare(strict_types=1);

namespace LogTide\SDK\Enums;

/**
 * Circuit breaker states
 */
enum CircuitState: string
{
    case CLOSED = 'CLOSED';
    case OPEN = 'OPEN';
    case HALF_OPEN = 'HALF_OPEN';
}
