<?php

declare(strict_types=1);

namespace LogWard\SDK\Models;

use LogWard\SDK\Enums\LogLevel;

/**
 * Single log entry
 */
class LogEntry
{
    public function __construct(
        public string $service,
        public LogLevel $level,
        public string $message,
        public ?string $time = null,
        public array $metadata = [],
        public ?string $traceId = null,
    ) {
        $this->time ??= (new \DateTime())->format('c');
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'level' => $this->level->value,
            'message' => $this->message,
            'time' => $this->time,
            'metadata' => $this->metadata,
            'trace_id' => $this->traceId,
        ];
    }
}
