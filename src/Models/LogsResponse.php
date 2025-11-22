<?php

declare(strict_types=1);

namespace LogWard\SDK\Models;

/**
 * Response from logs query
 */
readonly class LogsResponse
{
    /**
     * @param array<LogEntry> $logs
     */
    public function __construct(
        public array $logs,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
