<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">LogTide PHP SDK</h1>

<p align="center">
  <a href="https://packagist.org/packages/logtide/logtide"><img src="https://img.shields.io/packagist/v/logtide/logtide?color=blue" alt="Packagist"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.1+-purple.svg" alt="PHP"></a>
</p>

<p align="center">
  Official PHP SDK for <a href="https://logtide.dev">LogTide</a> with Hub/Scope architecture, tracing, breadcrumbs, and framework integrations.
</p>

---

## Packages

| Package | Description |
|---------|-------------|
| [`logtide/logtide`](packages/logtide) | Core SDK: Hub, Client, Scope, Transport, Integrations |
| [`logtide/logtide-laravel`](packages/logtide-laravel) | Laravel integration with ServiceProvider, Facade, middleware |
| [`logtide/logtide-symfony`](packages/logtide-symfony) | Symfony Bundle with event subscribers and Doctrine integration |
| [`logtide/logtide-slim`](packages/logtide-slim) | Slim 4 middleware and error handler |
| [`logtide/logtide-wordpress`](packages/logtide-wordpress) | WordPress plugin with DB/HTTP breadcrumbs |

## Quick Start

```bash
composer require logtide/logtide
```

```php
\LogTide\init([
    'dsn' => 'https://lp_your_api_key@your-logtide-instance.com',
    'service' => 'my-app',
    'environment' => 'production',
]);

// Log messages
\LogTide\info('User logged in', ['user_id' => 123]);
\LogTide\error('Payment failed', ['order_id' => 456]);

// Capture exceptions
try {
    riskyOperation();
} catch (\Throwable $e) {
    \LogTide\captureException($e);
}

// Breadcrumbs for context
\LogTide\addBreadcrumb(new \LogTide\Breadcrumb\Breadcrumb(
    \LogTide\Enum\BreadcrumbType::QUERY,
    'SELECT * FROM users WHERE id = 1',
    category: 'db.query',
));

// Scoped context
\LogTide\configureScope(function (\LogTide\State\Scope $scope) {
    $scope->setTag('request_id', 'abc-123');
    $scope->setUser(['id' => 42, 'email' => 'user@example.com']);
});

// Tracing
$span = \LogTide\startSpan('db.query', ['kind' => \LogTide\Enum\SpanKind::CLIENT]);
// ... do work ...
$span->finish(\LogTide\Enum\SpanStatus::OK);
\LogTide\finishSpan($span);
```

## Laravel

```bash
composer require logtide/logtide-laravel
```

Add to `.env`:
```
LOGTIDE_DSN=https://lp_your_api_key@your-logtide-instance.com
```

Publish config:
```bash
php artisan vendor:publish --tag=logtide-config
```

Everything is automatic: HTTP request tracing, exception capture, DB query breadcrumbs, cache/queue monitoring.

## Symfony

```bash
composer require logtide/logtide-symfony
```

```yaml
# config/packages/logtide.yaml
logtide:
    dsn: '%env(LOGTIDE_DSN)%'
    service: 'my-symfony-app'
    environment: '%kernel.environment%'
```

Automatically traces HTTP requests, console commands, and Doctrine queries.

## Slim

```bash
composer require logtide/logtide-slim
```

```php
use LogTide\Slim\LogtideMiddleware;

\LogTide\init(['dsn' => '...', 'service' => 'slim-api']);

$app = \Slim\Factory\AppFactory::create();
$app->add(new LogtideMiddleware());
```

## WordPress

```bash
composer require logtide/logtide-wordpress
```

```php
// In your plugin or functions.php
\LogTide\WordPress\LogtideWordPress::init([
    'dsn' => 'https://lp_your_api_key@your-logtide-instance.com',
    'service' => 'my-wordpress-site',
]);
```

Automatically captures: wp_die errors, database queries, HTTP API calls, plugin events, redirects.

## Architecture

```
\LogTide\init()  -->  LogtideSdk  -->  Hub (scope stack)  -->  Client  -->  Transport
                                        |                       |            |
                                        Scope                   |       BatchTransport
                                        - tags                  |        /        \
                                        - extras            Integrations  HttpTransport  OtlpHttpTransport
                                        - user                            (logs)          (spans)
                                        - breadcrumbs
                                        - span
                                        - propagation context
```

## Configuration

```php
\LogTide\init([
    'dsn' => 'https://lp_KEY@host',     // or use api_url + api_key
    'service' => 'my-app',
    'environment' => 'production',
    'release' => '1.2.0',
    'debug' => false,

    // Batching & resilience
    'batch_size' => 100,
    'max_buffer_size' => 10000,
    'max_retries' => 3,
    'retry_delay_ms' => 1000,
    'circuit_breaker_threshold' => 5,
    'circuit_breaker_reset_ms' => 30000,

    // Features
    'traces_sample_rate' => 1.0,
    'max_breadcrumbs' => 100,
    'attach_stacktrace' => false,
    'send_default_pii' => false,

    // Callbacks
    'before_send' => function (\LogTide\Event $event) {
        // modify or return null to drop
        return $event;
    },

    // Global context
    'tags' => ['region' => 'eu-west-1'],
    'global_metadata' => ['hostname' => gethostname()],
]);
```

## Testing

```bash
composer install
composer test
composer phpstan
```

## License

MIT License - see [LICENSE](LICENSE) for details.
