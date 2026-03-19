# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.1] - 2026-03-19

### Fixed

- Fixed `^0.1` version constraint on `logtide/logtide` in all integration packages — Composer treats `^0.1` as `>=0.1.0 <0.2.0`, so `v0.7.0` was not installable. Constraint updated to `^0.7` in `logtide-laravel`, `logtide-symfony`, `logtide-slim`, and `logtide-wordpress`. Closes [#5](https://github.com/logtide-dev/logtide-php/issues/5).

## [0.7.0] - 2026-03-06

### Added

#### Monorepo Structure
- Restructured as Composer monorepo with 5 packages under `packages/*`
- Unified test suite with PHPUnit 10.5 (265 tests, 570 assertions)
- PHPStan level 8 static analysis across all packages
- PSR-12 code style enforcement with PHP_CodeSniffer

#### Core (`logtide/logtide`)
- `LogtideSdk` - static entry point for SDK initialization
- `ClientBuilder` - fluent client construction with sensible defaults
- `Client` - capture logs, errors, breadcrumbs, and spans
- `Hub` - global singleton for convenient access across your app
- `Scope` - per-request context isolation with tags, extras, and breadcrumbs
- `BatchTransport` - automatic batching with retry logic and circuit breaker
- `HttpTransport` and `OtlpHttpTransport` for log and span delivery
- `CurlHttpClient` and `GuzzleHttpClient` HTTP client implementations
- DSN parsing, error serialization, trace ID generation
- W3C Trace Context (`traceparent`) propagation
- Breadcrumb buffer with configurable max size
- Monolog handlers: `LogtideHandler` and `BreadcrumbHandler`
- PSR-15 middleware for generic HTTP request tracing
- Global helper functions (`\LogTide\init()`, `\LogTide\captureException()`, etc.)
- Built-in integrations: Request, Environment, ExceptionListener, ErrorListener, FatalErrorListener

#### Laravel (`logtide/logtide-laravel`)
- `LogtideServiceProvider` with auto-discovery and publishable config
- `LogtideMiddleware` for automatic request tracing
- `LogChannel` for Laravel logging integration
- `LogtideFacade` for static access
- Breadcrumb integrations: DB queries, cache operations, queue jobs

#### Symfony (`logtide/logtide-symfony`)
- `LogtideBundle` with DI extension and semantic configuration
- `RequestSubscriber` for automatic HTTP request tracing
- `ConsoleSubscriber` for CLI command tracing
- `SymfonyIntegration` and `DoctrineIntegration` for breadcrumbs

#### Slim (`logtide/logtide-slim`)
- `LogtideMiddleware` - PSR-15 middleware for request tracing
- `LogtideErrorMiddleware` - error capture with full request context
- Automatic route pattern resolution from Slim routing

#### WordPress (`logtide/logtide-wordpress`)
- `LogtideWordPress` - static initializer with WordPress hook registration
- Lifecycle hooks: `wp_loaded`, `shutdown`, `wp_die_handler`, `wp_redirect`, `wp_mail`
- `WordPressIntegration` - PHP error handler integration
- `DatabaseIntegration` - slow query breadcrumbs via `$wpdb`
- `HttpApiIntegration` - outgoing HTTP request breadcrumbs
- Multisite support (blog switch tracking, plugin activation/deactivation)

#### CI/CD
- GitHub Actions CI: PHPUnit tests, PHPStan, PHPCS on push/PR to `main`/`develop`
- GitHub Actions publish: Packagist publish on tag `v*.*.*` or manual dispatch
- PHP version matrix: 8.1, 8.2, 8.3, 8.4
- Branch model: `develop` → `main`, hotfix directly to `main`

#### Documentation
- README for every package with badges, quick start, API reference
- Root README with package table, architecture diagram, development guide
- Contributing guide, Code of Conduct, Changelog
- Branch protection documentation (`.github/BRANCH_PROTECTION.md`)

## [0.1.0] - 2026-01-13

### Added

- Initial release of LogTide PHP SDK
- Automatic batching with configurable size and interval
- Retry logic with exponential backoff
- Circuit breaker pattern for fault tolerance
- Max buffer size with drop policy
- Query API for searching and filtering logs
- Live tail with Server-Sent Events (SSE)
- Aggregated statistics API
- Trace ID context for distributed tracing
- Global metadata support
- Structured error serialization
- Internal metrics tracking
- Logging methods: debug, info, warn, error, critical
- Laravel middleware for auto-logging HTTP requests
- Symfony event subscriber for auto-logging HTTP requests
- PSR-15 middleware for Slim, Mezzio, and other frameworks
- Full PHP 8.1+ support with strict types and enums

[0.7.1]: https://github.com/logtide-dev/logtide-php/releases/tag/v0.7.1
[0.7.0]: https://github.com/logtide-dev/logtide-php/releases/tag/v0.7.0
[0.1.0]: https://github.com/logtide-dev/logtide-php/releases/tag/v0.1.0
