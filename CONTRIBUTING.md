# Contributing to LogTide PHP SDK

Thank you for your interest in contributing!

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/logtide-dev/logtide-sdk-php.git
cd logtide-sdk-php
```

2. Install dependencies:
```bash
composer install
```

## Code Style

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use strict types (`declare(strict_types=1)`)
- Use PHP 8.1+ features (enums, named arguments, etc.)
- Add PHPDoc comments for public APIs
- Use meaningful variable and method names

## Testing

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage

# Run PHPStan static analysis
composer phpstan

# Check code style
composer cs

# Fix code style
composer cs:fix
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Ensure tests pass (`composer test`)
5. Run static analysis (`composer phpstan`)
6. Check code style (`composer cs`)
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
