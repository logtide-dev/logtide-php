<?php

declare(strict_types=1);

// This is a conceptual example for Symfony integration
// Add to config/services.yaml

use LogTide\SDK\LogTideClient;
use LogTide\SDK\Models\LogTideClientOptions;
use LogTide\SDK\Middleware\SymfonySubscriber;

// config/services.yaml:
services:
    LogTide\SDK\LogTideClient:
        factory: ['@App\Factory\LogTideClientFactory', 'create']

    LogTide\SDK\Middleware\SymfonySubscriber:
        arguments:
            $client: '@LogTide\SDK\LogTideClient'
            $serviceName: '%env(APP_NAME)%'
            $logRequests: true
            $logResponses: true
            $logErrors: true
        tags:
            - { name: kernel.event_subscriber }

// src/Factory/LogTideClientFactory.php:
namespace App\Factory;

use LogTide\SDK\LogTideClient;
use LogTide\SDK\Models\LogTideClientOptions;

class LogTideClientFactory
{
    public function create(): LogTideClient
    {
        return new LogTideClient(new LogTideClientOptions(
            apiUrl: $_ENV['LOGTIDE_API_URL'] ?? 'http://localhost:8080',
            apiKey: $_ENV['LOGTIDE_API_KEY'],
            debug: $_ENV['APP_DEBUG'] === 'true',
            globalMetadata: [
                'env' => $_ENV['APP_ENV'] ?? 'dev',
                'app' => $_ENV['APP_NAME'] ?? 'symfony-app',
            ],
        ));
    }
}

// Use in controllers:
namespace App\Controller;

use LogTide\SDK\LogTideClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController
{
    public function __construct(
        private readonly LogTideClient $logger
    ) {
    }

    #[Route('/users', name: 'users_list')]
    public function list(): JsonResponse
    {
        $this->logger->info('users', 'Fetching users list');

        // ... fetch users

        return new JsonResponse(['users' => []]);
    }
}
