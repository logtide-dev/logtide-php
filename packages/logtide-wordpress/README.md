<p align="center">
  <img src="https://raw.githubusercontent.com/logtide-dev/logtide/main/docs/images/logo.png" alt="LogTide Logo" width="400">
</p>

<h1 align="center">logtide/logtide-wordpress</h1>

<p align="center">
  <a href="https://packagist.org/packages/logtide/logtide-wordpress"><img src="https://img.shields.io/packagist/v/logtide/logtide-wordpress?color=blue" alt="Packagist"></a>
  <a href="../../LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
  <a href="https://wordpress.org/"><img src="https://img.shields.io/badge/WordPress-6.x-21759b.svg" alt="WordPress"></a>
</p>

<p align="center">
  <a href="https://logtide.dev">LogTide</a> integration for WordPress - automatic error capture, database monitoring, and breadcrumbs.
</p>

---

## Features

- **Automatic error capture** via `wp_die_handler` filter
- **Database query breadcrumbs** with slow query detection
- **HTTP API breadcrumbs** for outgoing WordPress HTTP requests
- **Lifecycle breadcrumbs** - `wp_loaded`, redirects, email sending
- **Plugin events** - activation/deactivation tracking
- **Multisite support** - blog switch tracking
- **WordPress error handler integration** via `set_error_handler`

## Installation

```bash
composer require logtide/logtide-wordpress
```

---

## Quick Start

Add to your plugin's main file or `functions.php`:

```php
use LogTide\WordPress\LogtideWordPress;

LogtideWordPress::init([
    'dsn' => 'https://lp_your_key@your-logtide-instance.com',
    'service' => 'my-wordpress-site',
    'environment' => 'production',
]);
```

Or with separate API URL and key:

```php
LogtideWordPress::init([
    'api_url' => 'https://your-logtide-instance.com',
    'api_key' => 'lp_your_key',
    'service' => 'my-wordpress-site',
]);
```

---

## How It Works

`LogtideWordPress::init()` registers WordPress hooks automatically:

| Hook | What it does |
|------|-------------|
| `wp_loaded` | Records a lifecycle breadcrumb |
| `shutdown` | Flushes all pending logs and spans |
| `wp_die_handler` | Captures `WP_Error` and string messages |
| `wp_redirect` | Records redirect breadcrumbs |
| `wp_mail` | Records outgoing email breadcrumbs |
| `switch_blog` | Records multisite blog switch |
| `activated_plugin` | Records plugin activation |
| `deactivated_plugin` | Records plugin deactivation |

---

## Integrations

### WordPressIntegration

Hooks into PHP's `set_error_handler` to capture warnings, notices, and fatal errors.

### DatabaseIntegration

Monitors `$wpdb` queries and records them as breadcrumbs. Highlights slow queries (configurable threshold, default 100ms).

```php
LogtideWordPress::init([
    'dsn' => '...',
    'service' => 'my-site',
    'slow_query_threshold_ms' => 200.0, // flag queries slower than 200ms
]);
```

### HttpApiIntegration

Records WordPress HTTP API calls (`wp_remote_get`, `wp_remote_post`, etc.) as breadcrumbs with URL, method, and response status.

---

## License

MIT License - see [LICENSE](../../LICENSE) for details.

## Links

- [LogTide Website](https://logtide.dev)
- [Documentation](https://logtide.dev/docs/sdks/wordpress/)
- [GitHub](https://github.com/logtide-dev/logtide-php)
