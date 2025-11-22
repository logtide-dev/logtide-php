<?php

declare(strict_types=1);

namespace LogWard\SDK\Exceptions;

/**
 * Exception thrown when buffer is full
 */
class BufferFullException extends LogWardException
{
    public function __construct(string $message = 'Log buffer is full, log dropped')
    {
        parent::__construct($message);
    }
}
