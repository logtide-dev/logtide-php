<?php

declare(strict_types=1);

// This is a conceptual example for Laravel integration
// Add to app/Http/Kernel.php or bootstrap/app.php

use App\Http\Middleware\LogWardMiddleware;
use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;

// In a Service Provider (e.g., AppServiceProvider):
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LogWardClient::class, function ($app) {
            return new LogWardClient(new LogWardClientOptions(
                apiUrl: env('LOGWARD_API_URL', 'http://localhost:8080'),
                apiKey: env('LOGWARD_API_KEY'),
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
    $middleware->append(LogWardMiddleware::class);
})

// Or in app/Http/Kernel.php (Laravel 10 and earlier):
protected $middleware = [
    // ...
    \LogWard\SDK\Middleware\LaravelMiddleware::class,
];

// Configure in config/services.php:
return [
    'logward' => [
        'client' => LogWardClient::class,
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
    public function __construct(private LogWardClient $logger)
    {
    }

    public function index()
    {
        $this->logger->info('users', 'Fetching users list');
        
        return User::all();
    }
}
