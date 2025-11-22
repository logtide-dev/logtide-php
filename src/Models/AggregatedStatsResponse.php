<?php

declare(strict_types=1);

namespace LogWard\SDK\Models;

/**
 * Aggregated statistics response
 */
readonly class AggregatedStatsResponse
{
    /**
     * @param array<array{bucket: string, total: int, by_level: array<string, int>}> $timeseries
     * @param array<array{service: string, count: int}> $topServices
     * @param array<array{message: string, count: int}> $topErrors
     */
    public function __construct(
        public array $timeseries,
        public array $topServices,
        public array $topErrors,
    ) {
    }
}
