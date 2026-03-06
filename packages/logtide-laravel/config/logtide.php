<?php

declare(strict_types=1);

return [
    'dsn' => env('LOGTIDE_DSN'),

    'service' => env('LOGTIDE_SERVICE', env('APP_NAME', 'laravel')),

    'environment' => env('LOGTIDE_ENVIRONMENT', env('APP_ENV', 'production')),

    'release' => env('LOGTIDE_RELEASE'),

    'batch_size' => (int) env('LOGTIDE_BATCH_SIZE', 100),

    'flush_interval' => (int) env('LOGTIDE_FLUSH_INTERVAL', 5000),

    'max_buffer_size' => (int) env('LOGTIDE_MAX_BUFFER_SIZE', 10000),

    'max_retries' => (int) env('LOGTIDE_MAX_RETRIES', 3),

    'traces_sample_rate' => (float) env('LOGTIDE_TRACES_SAMPLE_RATE', 1.0),

    'debug' => (bool) env('LOGTIDE_DEBUG', false),

    'send_default_pii' => (bool) env('LOGTIDE_SEND_DEFAULT_PII', false),

    'breadcrumbs' => [
        'db_queries' => true,
        'cache' => true,
        'queue' => true,
        'http_client' => true,
    ],

    // Paths to skip in the HTTP middleware
    'skip_paths' => [
        '/health',
        '/healthz',
    ],
];
