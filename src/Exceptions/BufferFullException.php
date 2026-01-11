<?php

declare(strict_types=1);

namespace LogTide\SDK\Exceptions;

/**
 * Exception thrown when buffer is full
 */
class BufferFullException extends LogTideException
{
    public function __construct(string $message = 'Log buffer is full, log dropped')
    {
        parent::__construct($message);
    }
}
