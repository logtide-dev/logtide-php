<?php

declare(strict_types=1);

namespace LogWard\SDK\Middleware;

use LogWard\SDK\LogWardClient;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Symfony event subscriber for automatic HTTP request/response logging
 */
class SymfonySubscriber implements EventSubscriberInterface
{
    private const REQUEST_START_TIME_ATTR = '_logward_start_time';

    public function __construct(
        private readonly LogWardClient $client,
        private readonly string $serviceName,
        private readonly bool $logRequests = true,
        private readonly bool $logResponses = true,
        private readonly bool $logErrors = true,
        private readonly array $skipPaths = [],
        private readonly bool $skipHealthCheck = true,
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip configured paths
        if ($this->shouldSkip($request)) {
            return;
        }

        // Store start time for duration calculation
        $request->attributes->set(self::REQUEST_START_TIME_ATTR, microtime(true));

        // Extract and set trace ID
        $traceId = $request->headers->get('x-trace-id');
        if ($traceId) {
            $this->client->setTraceId($traceId);
        }

        // Log request
        if ($this->logRequests) {
            $this->client->info($this->serviceName, "{$request->getMethod()} {$request->getPathInfo()}", [
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'query' => $request->query->all(),
                'ip' => $request->getClientIp(),
                'userAgent' => $request->headers->get('User-Agent'),
            ]);
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->shouldSkip($request)) {
            return;
        }

        if (!$this->logResponses) {
            return;
        }

        $startTime = $request->attributes->get(self::REQUEST_START_TIME_ATTR, microtime(true));
        $duration = (microtime(true) - $startTime) * 1000;
        $statusCode = $response->getStatusCode();
        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warn' : 'info');

        $message = "{$request->getMethod()} {$request->getPathInfo()} {$statusCode} ({$duration}ms)";
        $metadata = [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'statusCode' => $statusCode,
            'duration_ms' => $duration,
        ];

        match ($level) {
            'error' => $this->client->error($this->serviceName, $message, $metadata),
            'warn' => $this->client->warn($this->serviceName, $message, $metadata),
            default => $this->client->info($this->serviceName, $message, $metadata),
        };
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->logErrors) {
            return;
        }

        $exception = $event->getThrowable();
        $this->client->error($this->serviceName, "Request error: {$exception->getMessage()}", $exception);
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->getPathInfo();

        if ($this->skipHealthCheck && in_array($path, ['/health', '/healthz'], true)) {
            return true;
        }

        return in_array($path, $this->skipPaths, true);
    }
}
