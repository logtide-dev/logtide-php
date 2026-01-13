# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.0]: https://github.com/logtide-dev/logtide-sdk-php/releases/tag/v0.1.0
