<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;
use LogWard\SDK\Models\QueryOptions;
use LogWard\SDK\Models\AggregatedStatsOptions;
use LogWard\SDK\Enums\LogLevel;

// Initialize client with all options
$client = new LogWardClient(new LogWardClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',
    batchSize: 50,
    flushInterval: 3000,
    maxBufferSize: 5000,
    maxRetries: 3,
    retryDelayMs: 1000,
    circuitBreakerThreshold: 5,
    circuitBreakerResetMs: 30000,
    enableMetrics: true,
    debug: true,
    globalMetadata: [
        'env' => 'production',
        'version' => '1.0.0',
        'hostname' => gethostname(),
    ],
    autoTraceId: false,
));

// ==================== Logging ====================

echo "Sending logs...\n";

// Batch logging
for ($i = 0; $i < 10; $i++) {
    $client->info('worker', "Processing job #{$i}", ['job_id' => $i]);
}

$client->flush(); // Manual flush

// ==================== Query API ====================

echo "\nQuerying logs...\n";

$results = $client->query(new QueryOptions(
    service: 'api-gateway',
    level: LogLevel::ERROR,
    from: new DateTime('-24 hours'),
    to: new DateTime(),
    limit: 10,
));

echo "Found {$results->total} error logs\n";
foreach ($results->logs as $log) {
    echo "  - [{$log['time']}] {$log['message']}\n";
}

// ==================== Trace ID Query ====================

echo "\nQuerying by trace ID...\n";

$traceLogs = $client->getByTraceId('request-123');
echo "Found " . count($traceLogs) . " logs for trace request-123\n";

// ==================== Aggregated Stats ====================

echo "\nGetting aggregated stats...\n";

$stats = $client->getAggregatedStats(new AggregatedStatsOptions(
    from: new DateTime('-7 days'),
    to: new DateTime(),
    interval: '1d',
));

echo "Top services:\n";
foreach ($stats->topServices as $service) {
    echo "  - {$service['service']}: {$service['count']} logs\n";
}

// ==================== Metrics ====================

echo "\nSDK Metrics:\n";

$metrics = $client->getMetrics();
echo "  Logs sent: {$metrics->logsSent}\n";
echo "  Logs dropped: {$metrics->logsDropped}\n";
echo "  Errors: {$metrics->errors}\n";
echo "  Retries: {$metrics->retries}\n";
echo "  Avg latency: " . round($metrics->avgLatencyMs, 2) . "ms\n";
echo "  Circuit breaker trips: {$metrics->circuitBreakerTrips}\n";
echo "  Circuit breaker state: {$client->getCircuitBreakerState()->value}\n";

// ==================== Streaming (Demo) ====================

echo "\nStreaming logs (press Ctrl+C to stop)...\n";

// Note: This will block. Run in separate process in production.
// $client->stream(
//     onLog: function($log) {
//         echo "[{$log['time']}] {$log['level']}: {$log['message']}\n";
//     },
//     onError: function($error) {
//         echo "Stream error: {$error->getMessage()}\n";
//     },
//     filters: ['service' => 'api-gateway']
// );
