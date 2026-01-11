<?php

declare(strict_types=1);

namespace LogTide\SDK\Exceptions;

/**
 * Exception thrown when circuit breaker is OPEN
 */
class CircuitBreakerOpenException extends LogTideException
{
    public function __construct(string $message = 'Circuit breaker is OPEN, request blocked')
    {
        parent::__construct($message);
    }
}
