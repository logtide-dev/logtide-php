<?php

declare(strict_types=1);

namespace LogTide\SDK\Middleware;

use Closure;
use Illuminate\Http\Request;
use LogTide\SDK\LogTideClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel middleware for automatic HTTP request/response logging
 */
class LaravelMiddleware
{
    public function __construct(
        private readonly LogTideClient $client,
        private readonly string $serviceName,
        private readonly bool $logRequests = true,
        private readonly bool $logResponses = true,
        private readonly bool $logErrors = true,
        private readonly bool $includeHeaders = false,
        private readonly bool $includeBody = false,
        private readonly array $skipPaths = [],
        private readonly bool $skipHealthCheck = true,
    ) {
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip health checks
        if ($this->skipHealthCheck && in_array($request->path(), ['health', 'healthz'], true)) {
            return $next($request);
        }

        // Skip configured paths
        if (in_array($request->path(), $this->skipPaths, true)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $traceId = $request->header('x-trace-id') ?? $this->client->getTraceId();

        // Set trace ID context
        if ($traceId) {
            $this->client->setTraceId($traceId);
        }

        // Log incoming request
        if ($this->logRequests) {
            $metadata = [
                'method' => $request->method(),
                'path' => $request->path(),
                'query' => $request->query(),
                'ip' => $request->ip(),
                'userAgent' => $request->userAgent(),
            ];

            if ($this->includeHeaders) {
                $metadata['headers'] = $request->headers->all();
            }

            if ($this->includeBody && $request->getContent()) {
                $metadata['body'] = $request->all();
            }

            $this->client->info($this->serviceName, "{$request->method()} {$request->path()}", $metadata);
        }

        try {
            $response = $next($request);

            // Log response
            if ($this->logResponses) {
                $duration = (microtime(true) - $startTime) * 1000;
                $statusCode = $response->getStatusCode();
                $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warn' : 'info');

                $metadata = [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'statusCode' => $statusCode,
                    'duration_ms' => $duration,
                ];

                if ($this->includeHeaders) {
                    $metadata['responseHeaders'] = $response->headers->all();
                }

                $message = "{$request->method()} {$request->path()} {$statusCode} ({$duration}ms)";

                match ($level) {
                    'error' => $this->client->error($this->serviceName, $message, $metadata),
                    'warn' => $this->client->warn($this->serviceName, $message, $metadata),
                    default => $this->client->info($this->serviceName, $message, $metadata),
                };
            }

            return $response;
        } catch (\Throwable $e) {
            if ($this->logErrors) {
                $this->client->error($this->serviceName, "Request error: {$e->getMessage()}", $e);
            }

            throw $e;
        }
    }
}
