<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">LogTide PHP SDK</h1>

<p align="center">
  <a href="https://packagist.org/packages/logtide/sdk-php"><img src="https://img.shields.io/packagist/v/logtide/sdk-php?color=blue" alt="Packagist"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.1+-purple.svg" alt="PHP"></a>
  <a href="https://github.com/logtide-dev/logtide-sdk-php/releases"><img src="https://img.shields.io/github/v/release/logtide-dev/logtide-sdk-php" alt="Release"></a>
</p>

<p align="center">
  Official PHP SDK for <a href="https://logtide.dev">LogTide</a> with automatic batching, retry logic, circuit breaker, query API, live streaming, and middleware support.
</p>

---

## Features

- **Automatic batching** with configurable size and interval
- **Retry logic** with exponential backoff
- **Circuit breaker** pattern for fault tolerance
- **Max buffer size** with drop policy to prevent memory leaks
- **Query API** for searching and filtering logs
- **Live tail** with Server-Sent Events (SSE)
- **Trace ID context** for distributed tracing
- **Global metadata** added to all logs
- **Structured error serialization**
- **Internal metrics** (logs sent, errors, latency, etc.)
- **Laravel, Symfony & PSR-15 middleware** for auto-logging HTTP requests
- **Full PHP 8.1+ support** with strict types and enums

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require logtide/sdk-php
```

## Quick Start

```php
use LogTide\SDK\LogTideClient;
use LogTide\SDK\Models\LogTideClientOptions;

$client = new LogTideClient(new LogTideClientOptions(
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
| `apiUrl` | `string` | **required** | Base URL of your LogTide instance |
| `apiKey` | `string` | **required** | Project API key (starts with `lp_`) |
| `batchSize` | `int` | `100` | Number of logs to batch before sending |
| `flushInterval` | `int` | `5000` | Interval in ms to auto-flush logs |

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
$client = new LogTideClient(new LogTideClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',

    // Batching
    batchSize: 100,
    flushInterval: 5000,

    // Buffer management
    maxBufferSize: 10000,

    // Retry with exponential backoff (1s -> 2s -> 4s)
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
use LogTide\SDK\Enums\LogLevel;

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
use LogTide\SDK\Models\QueryOptions;
use LogTide\SDK\Enums\LogLevel;

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
use LogTide\SDK\Models\AggregatedStatsOptions;

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

## Middleware Integration

LogTide provides ready-to-use middleware for popular frameworks.

### Laravel Middleware

Auto-log all HTTP requests and responses.

```php
// app/Http/Kernel.php or bootstrap/app.php
use LogTide\SDK\Middleware\LaravelMiddleware;

$middleware->append(LaravelMiddleware::class);

// Service Provider
use LogTide\SDK\LogTideClient;
use LogTide\SDK\Models\LogTideClientOptions;

$this->app->singleton(LogTideClient::class, function() {
    return new LogTideClient(new LogTideClientOptions(
        apiUrl: env('LOGTIDE_API_URL'),
        apiKey: env('LOGTIDE_API_KEY'),
    ));
});
```

**Logged automatically:**
- Request: `POST /api/users`
- Response: `POST /api/users 201 (45ms)`
- Errors: `Request error: Internal Server Error`

### Symfony Event Subscriber

```yaml
# config/services.yaml
services:
    LogTide\SDK\Middleware\SymfonySubscriber:
        arguments:
            $client: '@LogTide\SDK\LogTideClient'
            $serviceName: '%env(APP_NAME)%'
        tags:
            - { name: kernel.event_subscriber }
```

### PSR-15 Middleware

Compatible with Slim, Mezzio, and other PSR-15 frameworks.

```php
use LogTide\SDK\Middleware\Psr15Middleware;

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

## Best Practices

### 1. Flush on Shutdown

```php
// Automatic cleanup via register_shutdown_function
// Or manually call:
$client->close();
```

### 2. Use Global Metadata

```php
$client = new LogTideClient(new LogTideClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',
    globalMetadata: [
        'env' => getenv('APP_ENV'),
        'version' => '1.0.0',
        'region' => 'us-east-1',
    ],
));
```

### 3. Enable Debug Mode in Development

```php
$client = new LogTideClient(new LogTideClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',
    debug: getenv('APP_ENV') === 'development',
));
```

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

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

MIT License - see [LICENSE](LICENSE) for details.

## Links

- [LogTide Website](https://logtide.dev)
- [Documentation](https://logtide.dev/docs/sdks/php/)
- [GitHub Issues](https://github.com/logtide-dev/logtide-sdk-php/issues)
