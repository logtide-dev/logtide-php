<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;

// Initialize client
$client = new LogWardClient(new LogWardClientOptions(
    apiUrl: 'http://localhost:8080',
    apiKey: 'lp_your_api_key_here',
));

// Send simple logs
$client->info('api-gateway', 'Server started', ['port' => 3000]);
$client->warn('cache', 'Cache miss', ['key' => 'user:123']);

// Error with exception
try {
    throw new \RuntimeException('Database connection timeout');
} catch (\Exception $e) {
    $client->error('database', 'Connection failed', $e);
}

// With trace ID context
$client->withTraceId('request-123', function() use ($client) {
    $client->info('api', 'Processing request');
    $client->info('database', 'Querying users');
    $client->info('api', 'Sending response');
});

// Auto-flush and cleanup on shutdown
echo "Logs sent! Check your LogWard dashboard.\n";
