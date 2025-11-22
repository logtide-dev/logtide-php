# LogWard PHP SDK

Official PHP SDK for LogWard with advanced features: automatic batching, retry logic, circuit breaker, query API, live streaming, and middleware support.

## Features

- ✅ **Automatic batching** with configurable size and interval
- ✅ **Retry logic** with exponential backoff
- ✅ **Circuit breaker** pattern for fault tolerance
- ✅ **Max buffer size** with drop policy to prevent memory leaks
- ✅ **Query API** for searching and filtering logs
- ✅ **Live tail** with Server-Sent Events (SSE)
- ✅ **Trace ID context** for distributed tracing
- ✅ **Global metadata** added to all logs
- ✅ **Structured error serialization**
- ✅ **Internal metrics** (logs sent, errors, latency, etc.)
- ✅ **Laravel, Symfony & PSR-15 middleware** for auto-logging HTTP requests
- ✅ **Full PHP 8.1+ support** with strict types and enums

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require logward/sdk-php
```

## Quick Start

```php
use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;

$client = new LogWardClient(new LogWardClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',
));

// Send logs
$client->info('api-gateway', 'Server started', ['port' => 3000]);
$client->error('database', 'Connection failed', new PDOException('Timeout'));

// Graceful shutdown (auto-handled via register_shutdown_function)
```

---

## Configuration Options

### Basic Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `apiUrl` | `string` | **required** | Base URL of your LogWard instance |
| `apiKey` | `string` | **required** | Project API key (starts with `lp_`) |
| `batchSize` | `int` | `100` | Number of logs to batch before sending |
| `flushInterval` | `int` | `5000` | Interval in ms to auto-flush logs (not actively used, flush on shutdown) |

### Advanced Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `maxBufferSize` | `int` | `10000` | Max logs in buffer (prevents memory leak) |
| `maxRetries` | `int` | `3` | Max retry attempts on failure |
| `retryDelayMs` | `int` | `1000` | Initial retry delay (exponential backoff) |
| `circuitBreakerThreshold` | `int` | `5` | Failures before opening circuit |
| `circuitBreakerResetMs` | `int` | `30000` | Time before retrying after circuit opens |
| `enableMetrics` | `bool` | `true` | Track internal metrics |
| `debug` | `bool` | `false` | Enable debug logging to error_log |
| `globalMetadata` | `array` | `[]` | Metadata added to all logs |
| `autoTraceId` | `bool` | `false` | Auto-generate trace IDs for logs |

### Example: Full Configuration

```php
$client = new LogWardClient(new LogWardClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',
    
    // Batching
    batchSize: 100,
    flushInterval: 5000,
    
    // Buffer management
    maxBufferSize: 10000,
    
    // Retry with exponential backoff (1s → 2s → 4s)
    maxRetries: 3,
    retryDelayMs: 1000,
    
    // Circuit breaker
    circuitBreakerThreshold: 5,
    circuitBreakerResetMs: 30000,
    
    // Metrics & debugging
    enableMetrics: true,
    debug: true,
    
    // Global context
    globalMetadata: [
        'env' => getenv('APP_ENV'),
        'version' => '1.0.0',
        'hostname' => gethostname(),
    ],
    
    // Auto trace IDs
    autoTraceId: false,
));
```

---

## Logging Methods

### Basic Logging

```php
use LogWard\SDK\Enums\LogLevel;

$client->debug('service-name', 'Debug message');
$client->info('service-name', 'Info message', ['userId' => 123]);
$client->warn('service-name', 'Warning message');
$client->error('service-name', 'Error message', ['custom' => 'data']);
$client->critical('service-name', 'Critical message');
```

### Error Logging with Auto-Serialization

The SDK automatically serializes `Throwable` objects:

```php
try {
    throw new RuntimeException('Database timeout');
} catch (Exception $e) {
    // Automatically serializes error with stack trace
    $client->error('database', 'Query failed', $e);
}
```

Generated log metadata:
```json
{
  "error": {
    "name": "RuntimeException",
    "message": "Database timeout",
    "stack": "...",
    "file": "/path/to/file.php",
    "line": 42
  }
}
```

---

## Trace ID Context

Track requests across services with trace IDs.

### Manual Trace ID

```php
$client->setTraceId('request-123');

$client->info('api', 'Request received');
$client->info('database', 'Querying users');
$client->info('api', 'Response sent');

$client->setTraceId(null); // Clear context
```

### Scoped Trace ID

```php
$client->withTraceId('request-456', function() use ($client) {
    $client->info('api', 'Processing in context');
    $client->warn('cache', 'Cache miss');
});
// Trace ID automatically restored after closure
```

### Auto-Generated Trace ID

```php
use Ramsey\Uuid\Uuid;

$client->withNewTraceId(function() use ($client) {
    $client->info('worker', 'Background job started');
    $client->info('worker', 'Job completed');
});
```

---

## Query API

Search and retrieve logs programmatically.

### Basic Query

```php
use LogWard\SDK\Models\QueryOptions;
use LogWard\SDK\Enums\LogLevel;

$result = $client->query(new QueryOptions(
    service: 'api-gateway',
    level: LogLevel::ERROR,
    from: new DateTime('-24 hours'),
    to: new DateTime(),
    limit: 100,
    offset: 0,
));

echo "Found {$result->total} logs\n";
foreach ($result->logs as $log) {
    print_r($log);
}
```

### Full-Text Search

```php
$result = $client->query(new QueryOptions(
    q: 'timeout',
    limit: 50,
));
```

### Get Logs by Trace ID

```php
$logs = $client->getByTraceId('trace-123');
echo "Trace has " . count($logs) . " logs\n";
```

### Aggregated Statistics

```php
use LogWard\SDK\Models\AggregatedStatsOptions;

$stats = $client->getAggregatedStats(new AggregatedStatsOptions(
    from: new DateTime('-7 days'),
    to: new DateTime(),
    interval: '1h',
));

foreach ($stats->topServices as $service) {
    echo "{$service['service']}: {$service['count']} logs\n";
}
```

---

## Live Streaming (SSE)

Stream logs in real-time using Server-Sent Events.

```php
$client->stream(
    onLog: function($log) {
        echo "[{$log['time']}] {$log['level']}: {$log['message']}\n";
    },
    onError: function($error) {
        echo "Stream error: {$error->getMessage()}\n";
    },
    filters: [
        'service' => 'api-gateway',
        'level' => 'error',
    ]
);

// Note: This blocks. Run in separate process for production.
```

---

## Metrics

Track SDK performance and health.

```php
$metrics = $client->getMetrics();

echo "Logs sent: {$metrics->logsSent}\n";
echo "Logs dropped: {$metrics->logsDropped}\n";
echo "Errors: {$metrics->errors}\n";
echo "Retries: {$metrics->retries}\n";
echo "Avg latency: {$metrics->avgLatencyMs}ms\n";
echo "Circuit breaker trips: {$metrics->circuitBreakerTrips}\n";

// Get circuit breaker state
echo $client->getCircuitBreakerState()->value; // CLOSED|OPEN|HALF_OPEN

// Reset metrics
$client->resetMetrics();
```

---

## Middleware

### Laravel Middleware

Auto-log all HTTP requests and responses.

```php
// app/Http/Kernel.php or bootstrap/app.php
use LogWard\SDK\Middleware\LaravelMiddleware;

$middleware->append(LaravelMiddleware::class);

// Service Provider
use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;

$this->app->singleton(LogWardClient::class, function() {
    return new LogWardClient(new LogWardClientOptions(
        apiUrl: env('LOGWARD_API_URL'),
        apiKey: env('LOGWARD_API_KEY'),
    ));
});
```

**Logged automatically:**
- Request: `POST /api/users`
- Response: `POST /api/users 201 (45ms)`
- Errors: `Request error: Internal Server Error`

### Symfony Event Subscriber

```php
// config/services.yaml
services:
    LogWard\SDK\Middleware\SymfonySubscriber:
        arguments:
            $client: '@LogWard\SDK\LogWardClient'
            $serviceName: '%env(APP_NAME)%'
        tags:
            - { name: kernel.event_subscriber }
```

### PSR-15 Middleware

Compatible with Slim, Mezzio, and other PSR-15 frameworks.

```php
use LogWard\SDK\Middleware\Psr15Middleware;

$app->add(new Psr15Middleware(
    client: $client,
    serviceName: 'slim-api',
));
```

---

## Examples

See the [examples/](./examples) directory for complete working examples:

- **[basic.php](./examples/basic.php)** - Simple usage
- **[advanced.php](./examples/advanced.php)** - All advanced features
- **[laravel.php](./examples/laravel.php)** - Laravel integration
- **[symfony.php](./examples/symfony.php)** - Symfony integration

---

## API Reference

### LogWardClient

#### Constructor
```php
new LogWardClient(LogWardClientOptions $options)
```

#### Logging Methods
- `log(LogEntry $entry): void`
- `debug(string $service, string $message, array $metadata = []): void`
- `info(string $service, string $message, array $metadata = []): void`
- `warn(string $service, string $message, array $metadata = []): void`
- `error(string $service, string $message, array|Throwable $metadataOrError = []): void`
- `critical(string $service, string $message, array|Throwable $metadataOrError = []): void`

#### Context Methods
- `setTraceId(?string $traceId): void`
- `getTraceId(): ?string`
- `withTraceId(string $traceId, callable $fn): mixed`
- `withNewTraceId(callable $fn): mixed`

#### Query Methods
- `query(QueryOptions $options): LogsResponse`
- `getByTraceId(string $traceId): array`
- `getAggregatedStats(AggregatedStatsOptions $options): AggregatedStatsResponse`

#### Streaming
- `stream(callable $onLog, ?callable $onError = null, array $filters = []): void`

#### Metrics
- `getMetrics(): ClientMetrics`
- `resetMetrics(): void`
- `getCircuitBreakerState(): CircuitState`

#### Lifecycle
- `flush(): void`
- `close(): void`

---

## Testing

Run the test suite:

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run with coverage
composer test:coverage

# Run PHPStan
composer phpstan

# Code style check
composer cs
```

---

## License

MIT

---

## Contributing

Contributions are welcome! Please open an issue or PR on [GitHub](https://github.com/logward-dev/logward-sdk-php).

---

## Support

- **Documentation**: [https://logward.dev/docs](https://logward.dev/docs)
- **Issues**: [GitHub Issues](https://github.com/logward-dev/logward-sdk-php/issues)
