<?php

declare(strict_types=1);

namespace LogWard\SDK\Models;

/**
 * Options for aggregated statistics query
 */
readonly class AggregatedStatsOptions
{
    public function __construct(
        public \DateTimeInterface $from,
        public \DateTimeInterface $to,
        public ?string $interval = null,
        public ?string $service = null,
    ) {
    }
}
