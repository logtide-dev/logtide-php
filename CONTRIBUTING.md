# Contributing to LogTide PHP SDK

Thank you for your interest in contributing!

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/logtide-dev/logtide-php.git
cd logtide-php
```

2. Install dependencies:
```bash
composer install
```

3. Run the test suite:
```bash
composer test
```

## Project Structure

This is a Composer monorepo. All packages live under `packages/`:

- `logtide` - Core client, hub, transports, and utilities
- `logtide-laravel` - Laravel ServiceProvider, Middleware, Log Channel
- `logtide-symfony` - Symfony Bundle with Event Subscribers
- `logtide-slim` - Slim 4 PSR-15 Middleware
- `logtide-wordpress` - WordPress hooks and integrations

## Code Style

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use `declare(strict_types=1)` in all PHP files
- Use PHP 8.1+ features (enums, named arguments, etc.)
- Add PHPDoc comments for public APIs
- Use meaningful variable and method names

```bash
# Check code style
composer cs

# Fix code style
composer cs:fix
```

## Testing

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Static analysis (PHPStan level 8)
composer phpstan
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Ensure tests pass (`composer test`)
5. Ensure static analysis passes (`composer phpstan`)
6. Ensure code style passes (`composer cs`)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

## Reporting Issues

- Use the GitHub issue tracker
- Provide clear description and reproduction steps
- Include PHP version and OS information
- Include relevant logs and error messages

## Questions?

Feel free to open an issue for any questions or discussions!
