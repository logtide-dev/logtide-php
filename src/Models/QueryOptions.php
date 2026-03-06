<?php

declare(strict_types=1);

namespace LogTide\SDK\Models;

use LogTide\SDK\Enums\LogLevel;

/**
 * Options for querying logs
 */
readonly class QueryOptions
{
    public function __construct(
        public ?string $service = null,
        public ?LogLevel $level = null,
        public ?\DateTimeInterface $from = null,
        public ?\DateTimeInterface $to = null,
        public ?string $q = null,
        public ?int $limit = null,
        public ?int $offset = null,
    ) {
    }
}
