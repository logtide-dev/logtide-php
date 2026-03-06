<?php

declare(strict_types=1);

namespace LogTide\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use LogTide\SDK\Enums\CircuitState;
use LogTide\SDK\Enums\LogLevel;
use LogTide\SDK\Exceptions\BufferFullException;
use LogTide\SDK\Models\AggregatedStatsOptions;
use LogTide\SDK\Models\AggregatedStatsResponse;
use LogTide\SDK\Models\ClientMetrics;
use LogTide\SDK\Models\LogEntry;
use LogTide\SDK\Models\LogsResponse;
use LogTide\SDK\Models\LogTideClientOptions;
use LogTide\SDK\Models\QueryOptions;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * LogTide PHP SDK Client
 *
 * Main client for sending logs to LogTide with automatic batching,
 * retry logic, circuit breaker, and query capabilities.
 */
class LogTideClient
{
    private readonly string $apiUrl;
    private readonly string $apiKey;
    private readonly int $batchSize;
    private readonly int $flushInterval;
    private readonly int $maxBufferSize;
    private readonly int $maxRetries;
    private readonly int $retryDelayMs;
    private readonly bool $enableMetrics;
    private readonly bool $debugMode;
    private readonly array $globalMetadata;
    private readonly bool $autoTraceId;

    /** @var array<LogEntry> */
    private array $buffer = [];

    private readonly Client $httpClient;
    private readonly CircuitBreaker $circuitBreaker;
    private readonly ClientMetrics $metrics;

    /** @var array<float> */
    private array $latencies = [];

    private ?string $currentTraceId = null;

    public function __construct(LogTideClientOptions $options)
    {
        $this->apiUrl = rtrim($options->apiUrl, '/');
        $this->apiKey = $options->apiKey;
        $this->batchSize = $options->batchSize;
        $this->flushInterval = $options->flushInterval;
        $this->maxBufferSize = $options->maxBufferSize;
        $this->maxRetries = $options->maxRetries;
        $this->retryDelayMs = $options->retryDelayMs;
        $this->enableMetrics = $options->enableMetrics;
        $this->debugMode = $options->debug;
        $this->globalMetadata = $options->globalMetadata;
        $this->autoTraceId = $options->autoTraceId;

        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);

        $this->circuitBreaker = new CircuitBreaker(
            $options->circuitBreakerThreshold,
            $options->circuitBreakerResetMs
        );

        $this->metrics = new ClientMetrics();

        // Register shutdown handler for graceful flush
        register_shutdown_function(function (): void {
            $this->close();
        });
    }

    // ==================== Context Management ====================

    /**
     * Set trace ID for subsequent logs
     * Automatically validates and normalizes to UUID v4
     */
    public function setTraceId(?string $traceId): void
    {
        $this->currentTraceId = $this->normalizeTraceId($traceId);
    }

    /**
     * Get current trace ID
     */
    public function getTraceId(): ?string
    {
        return $this->currentTraceId;
    }

    /**
     * Execute function with a specific trace ID context
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function withTraceId(string $traceId, callable $fn): mixed
    {
        $previousTraceId = $this->currentTraceId;
        $this->currentTraceId = $traceId;

        try {
            return $fn();
        } finally {
            $this->currentTraceId = $previousTraceId;
        }
    }

    /**
     * Execute function with a new auto-generated trace ID
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function withNewTraceId(callable $fn): mixed
    {
        return $this->withTraceId(Uuid::uuid4()->toString(), $fn);
    }

    // ==================== Logging Methods ====================

    /**
     * Log a custom entry
     */
    public function log(LogEntry $entry): void
    {
        // Check buffer size limit
        if (count($this->buffer) >= $this->maxBufferSize) {
            $this->metrics->logsDropped++;

            if ($this->debugMode) {
                error_log("[LogTide] Buffer full, dropping log: {$entry->message}");
            }

            throw new BufferFullException();
        }

        // Normalize trace ID
        $normalizedTraceId = $this->normalizeTraceId($entry->traceId)
            ?? $this->normalizeTraceId($this->currentTraceId)
            ?? ($this->autoTraceId ? Uuid::uuid4()->toString() : null);

        // Merge global metadata
        $entry->metadata = array_merge($this->globalMetadata, $entry->metadata);
        $entry->traceId = $normalizedTraceId;

        // Ensure time is set
        $entry->time ??= (new \DateTime())->format('c');

        $this->buffer[] = $entry;

        // Auto-flush if batch size reached
        if (count($this->buffer) >= $this->batchSize) {
            $this->flush();
        }
    }

    /**
     * Log debug message
     */
    public function debug(string $service, string $message, array $metadata = []): void
    {
        $this->log(new LogEntry($service, LogLevel::DEBUG, $message, metadata: $metadata));
    }

    /**
     * Log info message
     */
    public function info(string $service, string $message, array $metadata = []): void
    {
        $this->log(new LogEntry($service, LogLevel::INFO, $message, metadata: $metadata));
    }

    /**
     * Log warning message
     */
    public function warn(string $service, string $message, array $metadata = []): void
    {
        $this->log(new LogEntry($service, LogLevel::WARN, $message, metadata: $metadata));
    }

    /**
     * Log error message
     * @param array|Throwable $metadataOrError
     */
    public function error(string $service, string $message, array|Throwable $metadataOrError = []): void
    {
        $metadata = $metadataOrError instanceof Throwable
            ? ['error' => $this->serializeError($metadataOrError)]
            : $metadataOrError;

        $this->log(new LogEntry($service, LogLevel::ERROR, $message, metadata: $metadata));
    }

    /**
     * Log critical message
     * @param array|Throwable $metadataOrError
     */
    public function critical(string $service, string $message, array|Throwable $metadataOrError = []): void
    {
        $metadata = $metadataOrError instanceof Throwable
            ? ['error' => $this->serializeError($metadataOrError)]
            : $metadataOrError;

        $this->log(new LogEntry($service, LogLevel::CRITICAL, $message, metadata: $metadata));
    }

    // ==================== Flush with Retry & Circuit Breaker ====================

    /**
     * Flush buffered logs to LogTide API
     * Implements retry logic with exponential backoff and circuit breaker pattern
     */
    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        // Check circuit breaker
        if (!$this->circuitBreaker->canAttempt()) {
            $this->metrics->circuitBreakerTrips++;

            if ($this->debugMode) {
                error_log('[LogTide] Circuit breaker OPEN, skipping flush');
            }

            return;
        }

        $logs = $this->buffer;
        $this->buffer = [];

        $startTime = microtime(true);
        $lastError = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->httpClient->post("{$this->apiUrl}/api/v1/ingest", [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-API-Key' => $this->apiKey,
                    ],
                    'json' => [
                        'logs' => array_map(fn(LogEntry $entry) => $entry->toArray(), $logs),
                    ],
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    // Success
                    $this->circuitBreaker->recordSuccess();
                    $this->metrics->logsSent += count($logs);

                    if ($this->enableMetrics) {
                        $latency = (microtime(true) - $startTime) * 1000;
                        $this->updateLatency($latency);
                    }

                    if ($this->debugMode) {
                        error_log('[LogTide] Sent ' . count($logs) . ' logs successfully');
                    }

                    return;
                }

                throw new \RuntimeException("HTTP {$response->getStatusCode()}");
            } catch (GuzzleException | Throwable $e) {
                $lastError = $e;
                $this->metrics->errors++;

                if ($attempt < $this->maxRetries) {
                    $this->metrics->retries++;
                    $delay = $this->retryDelayMs * (2 ** $attempt);

                    if ($this->debugMode) {
                        error_log("[LogTide] Retry " . ($attempt + 1) . "/{$this->maxRetries} after {$delay}ms: {$e->getMessage()}");
                    }

                    usleep($delay * 1000); // usleep takes microseconds
                }
            }
        }

        // All retries failed
        $this->circuitBreaker->recordFailure();

        if ($this->debugMode) {
            error_log("[LogTide] Failed to send logs after {$this->maxRetries} retries: " . ($lastError?->getMessage() ?? 'Unknown error'));
        }

        // Re-add logs to buffer if not full
        if (count($this->buffer) + count($logs) <= $this->maxBufferSize) {
            $this->buffer = array_merge($logs, $this->buffer);
        } else {
            $this->metrics->logsDropped += count($logs);
        }
    }

    // ==================== Query Methods ====================

    /**
     * Query logs with filters
     */
    public function query(QueryOptions $options): LogsResponse
    {
        $params = [];

        if ($options->service) {
            $params['service'] = $options->service;
        }
        if ($options->level) {
            $params['level'] = $options->level->value;
        }
        if ($options->from) {
            $params['from'] = $options->from->format('c');
        }
        if ($options->to) {
            $params['to'] = $options->to->format('c');
        }
        if ($options->q) {
            $params['q'] = $options->q;
        }
        if ($options->limit !== null) {
            $params['limit'] = $options->limit;
        }
        if ($options->offset !== null) {
            $params['offset'] = $options->offset;
        }

        $url = "{$this->apiUrl}/api/v1/logs?" . http_build_query($params);

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return new LogsResponse(
                logs: $data['logs'] ?? [],
                total: $data['total'] ?? 0,
                limit: $data['limit'] ?? 0,
                offset: $data['offset'] ?? 0,
            );
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Query failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get logs by trace ID
     * @return array<LogEntry>
     */
    public function getByTraceId(string $traceId): array
    {
        $url = "{$this->apiUrl}/api/v1/logs/trace/{$traceId}";

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['logs'] ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Get by trace ID failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get aggregated statistics
     */
    public function getAggregatedStats(AggregatedStatsOptions $options): AggregatedStatsResponse
    {
        $params = [
            'from' => $options->from->format('c'),
            'to' => $options->to->format('c'),
        ];

        if ($options->interval) {
            $params['interval'] = $options->interval;
        }
        if ($options->service) {
            $params['service'] = $options->service;
        }

        $url = "{$this->apiUrl}/api/v1/logs/aggregated?" . http_build_query($params);

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return new AggregatedStatsResponse(
                timeseries: $data['timeseries'] ?? [],
                topServices: $data['top_services'] ?? [],
                topErrors: $data['top_errors'] ?? [],
            );
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Get aggregated stats failed: " . $e->getMessage(), 0, $e);
        }
    }

    // ==================== Streaming (SSE) ====================

    /**
     * Stream logs in real-time via Server-Sent Events
     * 
     * @param callable(array): void $onLog Callback for each log entry
     * @param callable(\Exception): void|null $onError Optional error callback
     * @param array<string, mixed> $filters Optional filters (service, level)
     */
    public function stream(callable $onLog, ?callable $onError = null, array $filters = []): void
    {
        $params = array_merge(['token' => $this->apiKey], $filters);
        $url = "{$this->apiUrl}/api/v1/logs/stream?" . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_WRITEFUNCTION => function ($curl, $data) use ($onLog, $onError) {
                // Parse SSE format
                if (str_starts_with($data, 'data: ')) {
                    $jsonData = substr($data, 6);
                    try {
                        $log = json_decode(trim($jsonData), true, 512, JSON_THROW_ON_ERROR);
                        $onLog($log);
                    } catch (\Exception $e) {
                        if ($onError) {
                            $onError($e);
                        }
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    // ==================== Metrics ====================

    /**
     * Get SDK metrics
     */
    public function getMetrics(): ClientMetrics
    {
        return clone $this->metrics;
    }

    /**
     * Reset SDK metrics
     */
    public function resetMetrics(): void
    {
        $this->metrics->logsSent = 0;
        $this->metrics->logsDropped = 0;
        $this->metrics->errors = 0;
        $this->metrics->retries = 0;
        $this->metrics->avgLatencyMs = 0.0;
        $this->metrics->circuitBreakerTrips = 0;
        $this->latencies = [];
    }

    /**
     * Get circuit breaker state
     */
    public function getCircuitBreakerState(): CircuitState
    {
        return $this->circuitBreaker->getState();
    }

    // ==================== Lifecycle ====================

    /**
     * Close client and flush remaining logs
     */
    public function close(): void
    {
        $this->flush();
    }

    // ==================== Private Helpers ====================

    /**
     * Validate and normalize trace ID to UUID v4
     */
    private function normalizeTraceId(?string $traceId): ?string
    {
        if (!$traceId) {
            return null;
        }

        if ($this->isValidUUID($traceId)) {
            return $traceId;
        }

        // Invalid UUID - generate a new one
        $newTraceId = Uuid::uuid4()->toString();

        if ($this->debugMode) {
            error_log("[LogTide] Invalid trace_id \"{$traceId}\" (must be UUID v4). Generated new UUID: {$newTraceId}");
        }

        return $newTraceId;
    }

    /**
     * Check if string is a valid UUID v4
     */
    private function isValidUUID(string $str): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $str);
    }

    /**
     * Serialize error/exception to array
     */
    private function serializeError(mixed $error): array
    {
        if ($error instanceof Throwable) {
            $result = [
                'name' => get_class($error),
                'message' => $error->getMessage(),
                'stack' => $error->getTraceAsString(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ];

            if ($previous = $error->getPrevious()) {
                $result['cause'] = $this->serializeError($previous);
            }

            return $result;
        }

        if (is_string($error)) {
            return ['message' => $error];
        }

        if (is_array($error)) {
            return $error;
        }

        return ['message' => (string) $error];
    }

    /**
     * Update latency metrics with rolling window
     */
    private function updateLatency(float $latency): void
    {
        $this->latencies[] = $latency;

        if (count($this->latencies) > 100) {
            array_shift($this->latencies);
        }

        $this->metrics->avgLatencyMs = array_sum($this->latencies) / count($this->latencies);
    }
}
