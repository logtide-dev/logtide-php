<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">logtide/logtide-slim</h1>

<p align="center">
  <a href="https://packagist.org/packages/logtide/logtide-slim"><img src="https://img.shields.io/packagist/v/logtide/logtide-slim?color=blue" alt="Packagist"></a>
  <a href="../../LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://www.slimframework.com/"><img src="https://img.shields.io/badge/Slim-4.x-74b566.svg" alt="Slim"></a>
</p>

<p align="center">
  <a href="https://logtide.dev">LogTide</a> middleware for Slim 4 — automatic request tracing, error capture, and breadcrumbs.
</p>

---

## Features

- **Automatic request spans** for every incoming request
- **Error capture** with full request context via error middleware
- **W3C Trace Context** propagation (`traceparent` in/out)
- **Route pattern resolution** from Slim routing
- **Configurable skip paths** (e.g. `/health`, `/healthz`)
- **PSR-15 compliant** middleware
- **Slim 4** support

## Installation

```bash
composer require logtide/logtide-slim
```

---

## Quick Start

```php
use LogTide\Slim\LogtideMiddleware;
use LogTide\Slim\LogtideErrorMiddleware;
use Slim\Factory\AppFactory;

// Initialize LogTide
\LogTide\init([
    'dsn' => 'https://lp_your_key@your-instance.com',
    'service' => 'my-slim-api',
]);

$app = AppFactory::create();

// Add LogTide request tracing middleware
$app->add(new LogtideMiddleware());

// Add routes
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write("Hello, {$args['name']}!");
    return $response;
});

// Option A: Use Slim's built-in error middleware (LogtideMiddleware captures errors too)
$app->addErrorMiddleware(true, true, true);

// Option B: Use LogtideErrorMiddleware as a standalone alternative
// $app->add(new LogtideErrorMiddleware($app->getResponseFactory()));

$app->run();
```

---

## How It Works

The middleware runs on every request and:

1. **Extracts** incoming `traceparent` header (or generates a new trace)
2. **Creates a span** named after the request (e.g. `HTTP GET /hello/{name}`)
3. **Records breadcrumbs** for request and response
4. **Calls** the next middleware/handler
5. **Finishes the span** with `ok` or `error` based on response status
6. **Injects `traceparent`** into the response headers
7. **Captures errors** for 5xx responses with full context

---

## Configuration

### Skip Paths

```php
$middleware = new LogtideMiddleware(
    skipPaths: ['/health', '/healthz', '/metrics'],
);
```

### Error Middleware

`LogtideErrorMiddleware` is a standalone alternative to Slim's built-in `addErrorMiddleware`. It captures exceptions, logs them to LogTide, and renders an error response.

```php
use LogTide\Slim\LogtideErrorMiddleware;

// Use instead of $app->addErrorMiddleware()
$app->add(new LogtideErrorMiddleware(
    responseFactory: $app->getResponseFactory(),
    displayErrorDetails: true, // show details in dev
));
```

---

## License

MIT License - see [LICENSE](../../LICENSE) for details.

## Links

- [LogTide Website](https://logtide.dev)
- [Documentation](https://logtide.dev/docs/sdks/slim/)
- [GitHub](https://github.com/logtide-dev/logtide-php)
