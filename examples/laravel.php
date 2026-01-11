<?php

declare(strict_types=1);

// This is a conceptual example for Laravel integration
// Add to app/Http/Kernel.php or bootstrap/app.php

use App\Http\Middleware\LogTideMiddleware;
use LogTide\SDK\LogTideClient;
use LogTide\SDK\Models\LogTideClientOptions;

// In a Service Provider (e.g., AppServiceProvider):
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LogTideClient::class, function ($app) {
            return new LogTideClient(new LogTideClientOptions(
                apiUrl: env('LOGTIDE_API_URL', 'http://localhost:8080'),
                apiKey: env('LOGTIDE_API_KEY'),
                debug: env('APP_DEBUG', false),
                globalMetadata: [
                    'env' => env('APP_ENV'),
                    'app' => env('APP_NAME'),
                ],
            ));
        });
    }
}

// Register middleware in bootstrap/app.php (Laravel 11+):
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(LogTideMiddleware::class);
})

// Or in app/Http/Kernel.php (Laravel 10 and earlier):
protected $middleware = [
    // ...
    \LogTide\SDK\Middleware\LaravelMiddleware::class,
];

// Configure in config/services.php:
return [
    'logtide' => [
        'client' => LogTideClient::class,
        'service_name' => env('APP_NAME', 'laravel-app'),
        'log_requests' => true,
        'log_responses' => true,
        'log_errors' => true,
        'skip_health_check' => true,
    ],
];

// Use directly in controllers:
class UserController extends Controller
{
    public function __construct(private LogTideClient $logger)
    {
    }

    public function index()
    {
        $this->logger->info('users', 'Fetching users list');

        return User::all();
    }
}
