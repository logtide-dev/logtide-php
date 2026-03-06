<?php

declare(strict_types=1);

namespace LogTide\Laravel;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use LogTide\Laravel\Integration\CacheBreadcrumbIntegration;
use LogTide\Laravel\Integration\QueryBreadcrumbIntegration;
use LogTide\Laravel\Integration\QueueIntegration;
use LogTide\LogtideSdk;
use LogTide\State\HubInterface;

class LogtideServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/logtide.php', 'logtide');

        $this->app->singleton(HubInterface::class, function (): HubInterface {
            $config = $this->app['config']->get('logtide', []);

            return LogtideSdk::init([
                'dsn' => $config['dsn'] ?? null,
                'service' => $config['service'] ?? 'laravel',
                'environment' => $config['environment'] ?? 'production',
                'release' => $config['release'] ?? null,
                'batch_size' => $config['batch_size'] ?? 100,
                'flush_interval' => $config['flush_interval'] ?? 5000,
                'max_buffer_size' => $config['max_buffer_size'] ?? 10000,
                'max_retries' => $config['max_retries'] ?? 3,
                'traces_sample_rate' => $config['traces_sample_rate'] ?? 1.0,
                'debug' => $config['debug'] ?? false,
                'send_default_pii' => $config['send_default_pii'] ?? false,
            ]);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/logtide.php' => $this->app->configPath('logtide.php'),
        ], 'logtide-config');

        $this->registerMiddleware();
        $this->registerLogChannel();

        $config = $this->app['config']->get('logtide.breadcrumbs', []);

        if ($config['db_queries'] ?? true) {
            $this->app->make(QueryBreadcrumbIntegration::class)->register();
        }

        if ($config['cache'] ?? true) {
            $this->app->make(CacheBreadcrumbIntegration::class)->register();
        }

        if ($config['queue'] ?? true) {
            $this->app->make(QueueIntegration::class)->register();
        }
    }

    private function registerMiddleware(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(LogtideMiddleware::class);
    }

    private function registerLogChannel(): void
    {
        $this->app['config']->set('logging.channels.logtide', [
            'driver' => 'custom',
            'via' => LogChannel::class,
            'level' => 'debug',
        ]);
    }
}
