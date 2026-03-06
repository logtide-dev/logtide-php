<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">logtide/logtide-laravel</h1>

<p align="center">
  <a href="https://packagist.org/packages/logtide/logtide-laravel"><img src="https://img.shields.io/packagist/v/logtide/logtide-laravel?color=blue" alt="Packagist"></a>
  <a href="../../LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-10%2F11%2F12-FF2D20.svg" alt="Laravel"></a>
</p>

<p align="center">
  <a href="https://logtide.dev">LogTide</a> integration for Laravel - automatic request tracing, error capture, and breadcrumbs.
</p>

---

## Features

- **Automatic request tracing** via HTTP middleware
- **Error capture** with full request context
- **W3C Trace Context** propagation (`traceparent` in/out)
- **Laravel Log Channel** - use LogTide as a logging driver
- **Facade** - `Logtide::info(...)` static access
- **Breadcrumb integrations** - DB queries, cache operations, queue jobs
- **Auto-discovery** - zero configuration needed
- **Laravel 10, 11, and 12** support

## Installation

```bash
composer require logtide/logtide-laravel
```

The service provider is auto-discovered. No manual registration needed.

---

## Quick Start

1. Add your DSN to `.env`:

```env
LOGTIDE_DSN=https://lp_your_key@your-logtide-instance.com
```

2. Optionally publish the config:

```bash
php artisan vendor:publish --tag=logtide-config
```

That's it! HTTP requests are automatically traced, exceptions captured, and DB queries recorded as breadcrumbs.

---

## Configuration

```php
// config/logtide.php

return [
    'dsn' => env('LOGTIDE_DSN'),
    'service' => env('LOGTIDE_SERVICE', env('APP_NAME', 'laravel')),
    'environment' => env('LOGTIDE_ENVIRONMENT', env('APP_ENV', 'production')),
    'release' => env('LOGTIDE_RELEASE'),
    'batch_size' => (int) env('LOGTIDE_BATCH_SIZE', 100),
    'flush_interval' => (int) env('LOGTIDE_FLUSH_INTERVAL', 5000),
    'max_buffer_size' => (int) env('LOGTIDE_MAX_BUFFER_SIZE', 10000),
    'max_retries' => (int) env('LOGTIDE_MAX_RETRIES', 3),
    'traces_sample_rate' => (float) env('LOGTIDE_TRACES_SAMPLE_RATE', 1.0),
    'debug' => (bool) env('LOGTIDE_DEBUG', false),
    'send_default_pii' => (bool) env('LOGTIDE_SEND_DEFAULT_PII', false),
    'breadcrumbs' => [
        'db_queries' => true,
        'cache' => true,
        'queue' => true,
        'http_client' => true,
    ],
    'skip_paths' => ['/health', '/healthz'],
];
```

---

## Using the Log Channel

Add LogTide as a logging channel in `config/logging.php`:

```php
'channels' => [
    'logtide' => [
        'driver' => 'custom',
        'via' => \LogTide\Laravel\LogChannel::class,
        'level' => 'debug',
    ],
],
```

Then use it:

```php
Log::channel('logtide')->info('Hello from Laravel!');

// Or set as default in .env:
// LOG_CHANNEL=logtide
```

---

## Using the Facade

```php
use LogTide\Laravel\LogtideFacade as Logtide;

Logtide::info('User logged in', ['user_id' => 123]);
Logtide::captureException($exception);
```

---

## Breadcrumb Integrations

All enabled by default in `config/logtide.php`:

- **DB Queries** - records SQL queries as breadcrumbs (via `QueryBreadcrumbIntegration`)
- **Cache** - records cache hits, misses, writes (via `CacheBreadcrumbIntegration`)
- **Queue** - records job processing events (via `QueueIntegration`)

---

## License

MIT License - see [LICENSE](../../LICENSE) for details.

## Links

- [LogTide Website](https://logtide.dev)
- [Documentation](https://logtide.dev/docs/sdks/laravel/)
- [GitHub](https://github.com/logtide-dev/logtide-php)
