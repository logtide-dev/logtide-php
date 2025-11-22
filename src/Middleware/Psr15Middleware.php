<?php

declare(strict_types=1);

namespace LogWard\SDK\Middleware;

use LogWard\SDK\LogWardClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware for automatic HTTP request/response logging
 * Compatible with Slim, Mezzio, and other PSR-15 compliant frameworks
 */
class Psr15Middleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LogWardClient $client,
        private readonly string $serviceName,
        private readonly bool $logRequests = true,
        private readonly bool $logResponses = true,
        private readonly array $skipPaths = [],
        private readonly bool $skipHealthCheck = true,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Skip paths
        if ($this->shouldSkip($path)) {
            return $handler->handle($request);
        }

        $startTime = microtime(true);
        $method = $request->getMethod();

        // Extract trace ID
        $traceId = $request->getHeaderLine('x-trace-id') ?: $this->client->getTraceId();
        if ($traceId) {
            $this->client->setTraceId($traceId);
        }

        // Log request
        if ($this->logRequests) {
            $this->client->info($this->serviceName, "{$method} {$path}", [
                'method' => $method,
                'path' => $path,
                'query' => $request->getQueryParams(),
                'userAgent' => $request->getHeaderLine('User-Agent'),
            ]);
        }

        $response = $handler->handle($request);

        // Log response
        if ($this->logResponses) {
            $duration = (microtime(true) - $startTime) * 1000;
            $statusCode = $response->getStatusCode();
            $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warn' : 'info');

            $message = "{$method} {$path} {$statusCode} ({$duration}ms)";
            $metadata = [
                'method' => $method,
                'path' => $path,
                'statusCode' => $statusCode,
                'duration_ms' => $duration,
            ];

            match ($level) {
                'error' => $this->client->error($this->serviceName, $message, $metadata),
                'warn' => $this->client->warn($this->serviceName, $message, $metadata),
                default => $this->client->info($this->serviceName, $message, $metadata),
            };
        }

        return $response;
    }

    private function shouldSkip(string $path): bool
    {
        if ($this->skipHealthCheck && in_array($path, ['/health', '/healthz'], true)) {
            return true;
        }

        return in_array($path, $this->skipPaths, true);
    }
}
