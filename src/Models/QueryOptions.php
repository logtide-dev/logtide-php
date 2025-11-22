<?php

declare(strict_types=1);

namespace LogWard\SDK\Models;

use LogWard\SDK\Enums\LogLevel;

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
