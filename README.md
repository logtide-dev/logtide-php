<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">LogTide PHP SDK</h1>

<p align="center">
  <a href="https://github.com/logtide-dev/logtide-php/releases"><img src="https://img.shields.io/github/v/release/logtide-dev/logtide-php" alt="Release"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://github.com/logtide-dev/logtide-php/actions"><img src="https://img.shields.io/github/actions/workflow/status/logtide-dev/logtide-php/ci.yml?branch=main" alt="CI"></a>
</p>

<p align="center">
  Official PHP SDKs for <a href="https://logtide.dev">LogTide</a> - self-hosted log management with distributed tracing, error capture, and breadcrumbs for every major framework.
</p>

---

## Packages

| Package | Version | Description |
|---------|---------|-------------|
| [`logtide/logtide`](./packages/logtide) | [![Packagist](https://img.shields.io/packagist/v/logtide/logtide?color=blue)](https://packagist.org/packages/logtide/logtide) | Core client, hub, transports, and utilities |
| [`logtide/logtide-laravel`](./packages/logtide-laravel) | [![Packagist](https://img.shields.io/packagist/v/logtide/logtide-laravel?color=blue)](https://packagist.org/packages/logtide/logtide-laravel) | Laravel integration |
| [`logtide/logtide-symfony`](./packages/logtide-symfony) | [![Packagist](https://img.shields.io/packagist/v/logtide/logtide-symfony?color=blue)](https://packagist.org/packages/logtide/logtide-symfony) | Symfony Bundle |
| [`logtide/logtide-slim`](./packages/logtide-slim) | [![Packagist](https://img.shields.io/packagist/v/logtide/logtide-slim?color=blue)](https://packagist.org/packages/logtide/logtide-slim) | Slim 4 middleware |
| [`logtide/logtide-wordpress`](./packages/logtide-wordpress) | [![Packagist](https://img.shields.io/packagist/v/logtide/logtide-wordpress?color=blue)](https://packagist.org/packages/logtide/logtide-wordpress) | WordPress integration |

## Quick Start

Every framework package follows the same pattern - pass your DSN and service name:

```bash
# Install for your framework
composer require logtide/logtide-laravel    # Laravel
composer require logtide/logtide-symfony    # Symfony
composer require logtide/logtide-slim       # Slim 4
composer require logtide/logtide-wordpress  # WordPress
composer require logtide/logtide            # Core (standalone)
```

```php
// Every integration follows the same pattern:
\LogTide\init([
    'dsn' => 'https://lp_your_key@your-logtide-instance.com',
    'service' => 'my-app',
]);

// Or use api_url + api_key separately:
\LogTide\init([
    'api_url' => 'https://your-logtide-instance.com',
    'api_key' => 'lp_your_key',
    'service' => 'my-app',
]);
```

See each package's README for framework-specific setup instructions.

---

## Architecture

```
logtide/logtide                ← Core: Client, Hub, Scope, Transports, Integrations
    ↓
├── logtide/logtide-laravel    ← Laravel ServiceProvider, Middleware, Log Channel
├── logtide/logtide-symfony    ← Symfony Bundle, Event Subscribers
├── logtide/logtide-slim       ← Slim 4 PSR-15 Middleware
└── logtide/logtide-wordpress  ← WordPress hooks & integrations
```

All framework packages share `logtide/logtide` core for:
- **Distributed tracing** (W3C Trace Context / `traceparent`)
- **Error serialization** with structured stack traces
- **Breadcrumbs** for HTTP, database, and custom events
- **Batched transport** with retry logic and circuit breaker
- **Scope isolation** per request
- **Monolog integration** for logging

---

## Development

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Static analysis (PHPStan level 8)
composer phpstan

# Code style check (PSR-12)
composer cs

# Code style fix
composer cs:fix
```

## Branch Model

```
feature/* ──> develop ──> main ──> tag v*.*.* ──> Packagist publish
hotfix/*  ──> main (via PR, for urgent fixes)
```

See [`.github/BRANCH_PROTECTION.md`](.plans/BRANCH_PROTECTION.md) for full details.

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## License

MIT License - see [LICENSE](LICENSE) for details.

## Links

- [LogTide Website](https://logtide.dev)
- [Documentation](https://logtide.dev/docs)
- [GitHub Issues](https://github.com/logtide-dev/logtide-php/issues)
