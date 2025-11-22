<?php

declare(strict_types=1);

namespace LogWard\SDK\Exceptions;

/**
 * Exception thrown when circuit breaker is OPEN
 */
class CircuitBreakerOpenException extends LogWardException
{
    public function __construct(string $message = 'Circuit breaker is OPEN, request blocked')
    {
        parent::__construct($message);
    }
}
