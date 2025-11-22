<?php

declare(strict_types=1);

// This is a conceptual example for Symfony integration
// Add to config/services.yaml

use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;
use LogWard\SDK\Middleware\SymfonySubscriber;

// config/services.yaml:
services:
    LogWard\SDK\LogWardClient:
        factory: ['@App\Factory\LogWardClientFactory', 'create']

    LogWard\SDK\Middleware\SymfonySubscriber:
        arguments:
            $client: '@LogWard\SDK\LogWardClient'
            $serviceName: '%env(APP_NAME)%'
            $logRequests: true
            $logResponses: true
            $logErrors: true
        tags:
            - { name: kernel.event_subscriber }

// src/Factory/LogWardClientFactory.php:
namespace App\Factory;

use LogWard\SDK\LogWardClient;
use LogWard\SDK\Models\LogWardClientOptions;

class LogWardClientFactory
{
    public function create(): LogWardClient
    {
        return new LogWardClient(new LogWardClientOptions(
            apiUrl: $_ENV['LOGWARD_API_URL'] ?? 'http://localhost:8080',
            apiKey: $_ENV['LOGWARD_API_KEY'],
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

use LogWard\SDK\LogWardClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController
{
    public function __construct(
        private readonly LogWardClient $logger
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
