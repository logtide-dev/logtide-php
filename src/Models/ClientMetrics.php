<?php

declare(strict_types=1);

namespace LogTide\SDK\Models;

/**
 * Client metrics tracking
 */
class ClientMetrics
{
    public int $logsSent = 0;
    public int $logsDropped = 0;
    public int $errors = 0;
    public int $retries = 0;
    public float $avgLatencyMs = 0.0;
    public int $circuitBreakerTrips = 0;
}
