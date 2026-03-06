<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">logtide/logtide-symfony</h1>

<p align="center">
  <a href="https://packagist.org/packages/logtide/logtide-symfony"><img src="https://img.shields.io/packagist/v/logtide/logtide-symfony?color=blue" alt="Packagist"></a>
  <a href="../../LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://symfony.com/"><img src="https://img.shields.io/badge/Symfony-6.4%2F7.x-000000.svg" alt="Symfony"></a>
</p>

<p align="center">
  <a href="https://logtide.dev">LogTide</a> Bundle for Symfony - automatic request tracing, error capture, and breadcrumbs.
</p>

---

## Features

- **Automatic request tracing** via `RequestSubscriber`
- **Console command tracing** via `ConsoleSubscriber`
- **W3C Trace Context** propagation (`traceparent` in/out)
- **Doctrine breadcrumbs** for database query tracking
- **Semantic configuration** - standard Symfony YAML/XML config
- **Symfony 6.4 and 7.x** support

## Installation

```bash
composer require logtide/logtide-symfony
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    LogTide\Symfony\LogtideBundle::class => ['all' => true],
];
```

---

## Quick Start

```yaml
# config/packages/logtide.yaml
logtide:
    dsn: '%env(LOGTIDE_DSN)%'
    service: 'my-symfony-app'
    environment: '%kernel.environment%'
```

Add `LOGTIDE_DSN` to your `.env`:

```env
LOGTIDE_DSN=https://lp_your_key@your-logtide-instance.com
```

---

## Configuration

```yaml
logtide:
    dsn: ~                       # LogTide DSN
    service: 'symfony'           # Service name
    environment: ~               # Environment (production, staging, ...)
    release: ~                   # Release / version identifier
    batch_size: 100              # Logs to batch before sending
    flush_interval: 5000         # Auto-flush interval in ms
    max_buffer_size: 10000       # Max logs in buffer
    max_retries: 3               # Max retry attempts
    traces_sample_rate: 1.0      # Sample rate for traces (0.0 to 1.0)
    debug: false                 # Enable debug logging
    send_default_pii: false      # Send personally identifiable information
```

---

## Event Subscribers

### RequestSubscriber

Automatically traces HTTP requests:
- Starts a span on `kernel.request`
- Finishes the span on `kernel.response`
- Captures errors on `kernel.exception`
- Propagates `traceparent` headers

### ConsoleSubscriber

Traces CLI commands:
- Starts a span on `console.command`
- Finishes the span on `console.terminate`
- Captures errors on `console.error`

---

## Integrations

### SymfonyIntegration

Captures Symfony-specific context (kernel info, route parameters).

### DoctrineIntegration

Records Doctrine SQL queries as breadcrumbs. Requires `doctrine/dbal`.

---

## License

MIT License - see [LICENSE](../../LICENSE) for details.

## Links

- [LogTide Website](https://logtide.dev)
- [Documentation](https://logtide.dev/docs/sdks/symfony/)
- [GitHub](https://github.com/logtide-dev/logtide-php)
