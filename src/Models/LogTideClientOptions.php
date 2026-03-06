<?php

declare(strict_types=1);

namespace LogTide\SDK\Models;

/**
 * Configuration options for LogTide client
 */
readonly class LogTideClientOptions
{
    public function __construct(
        public string $apiUrl,
        public string $apiKey,
        public int $batchSize = 100,
        public int $flushInterval = 5000,
        public int $maxBufferSize = 10000,
        public int $maxRetries = 3,
        public int $retryDelayMs = 1000,
        public int $circuitBreakerThreshold = 5,
        public int $circuitBreakerResetMs = 30000,
        public bool $enableMetrics = true,
        public bool $debug = false,
        public array $globalMetadata = [],
        public bool $autoTraceId = false,
    ) {
    }
}
