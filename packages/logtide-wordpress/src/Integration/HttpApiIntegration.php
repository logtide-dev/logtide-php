<?php

declare(strict_types=1);

namespace LogTide\WordPress\Integration;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Enum\SpanKind;
use LogTide\Enum\SpanStatus;
use LogTide\Integration\IntegrationInterface;
use LogTide\LogtideSdk;
use LogTide\Tracing\Span;

class HttpApiIntegration implements IntegrationInterface
{
    /** @var array<string, array{span: ?Span, start: float, url: string, method: string}> */
    private array $pendingRequests = [];

    public function getName(): string
    {
        return 'wordpress.http';
    }

    public function setupOnce(): void
    {
        add_filter('pre_http_request', [$this, 'onPreRequest'], 10, 3);
        add_filter('http_response', [$this, 'onResponse'], 10, 3);
    }

    public function teardown(): void
    {
    }

    /**
     * @param false|array|\WP_Error $response
     * @param array $parsedArgs
     * @param string $url
     * @return false|array|\WP_Error
     */
    public function onPreRequest(mixed $response, array $parsedArgs, string $url): mixed
    {
        $hub = LogtideSdk::getCurrentHub();
        $method = strtoupper($parsedArgs['method'] ?? 'GET');
        $requestKey = $method . '|' . $url;

        $hub->addBreadcrumb(new Breadcrumb(
            BreadcrumbType::HTTP,
            "{$method} {$url}",
            category: 'http.outbound',
            data: [
                'method' => $method,
                'url' => $url,
            ],
        ));

        $span = $hub->startSpan("HTTP {$method}", [
            'kind' => SpanKind::CLIENT,
        ]);

        $span?->setAttributes([
            'http.method' => $method,
            'http.url' => $url,
        ]);

        $this->pendingRequests[$requestKey] = [
            'span' => $span,
            'start' => microtime(true),
            'url' => $url,
            'method' => $method,
        ];

        return $response;
    }

    /**
     * @param array|\WP_Error $response
     * @param array $parsedArgs
     * @param string $url
     * @return array|\WP_Error
     */
    public function onResponse(mixed $response, array $parsedArgs, string $url): mixed
    {
        $hub = LogtideSdk::getCurrentHub();
        $method = strtoupper($parsedArgs['method'] ?? 'GET');
        $requestKey = $method . '|' . $url;

        $pending = $this->pendingRequests[$requestKey] ?? null;
        unset($this->pendingRequests[$requestKey]);

        $duration = $pending !== null
            ? (microtime(true) - $pending['start']) * 1000
            : 0.0;

        $span = $pending['span'] ?? null;

        if ($response instanceof \WP_Error) {
            $errorMessage = $response->get_error_message();

            $span?->setStatus(SpanStatus::ERROR, $errorMessage);
            if ($span !== null) {
                $hub->finishSpan($span);
            }

            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::HTTP,
                "HTTP error: {$errorMessage}",
                category: 'http.outbound',
                level: LogLevel::ERROR,
                data: [
                    'method' => $method,
                    'url' => $url,
                    'error' => $errorMessage,
                    'duration_ms' => round($duration, 2),
                ],
            ));

            return $response;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $span?->setAttribute('http.status_code', $statusCode);

        if ($statusCode >= 400) {
            $span?->setStatus(SpanStatus::ERROR);
        } else {
            $span?->setStatus(SpanStatus::OK);
        }

        if ($span !== null) {
            $hub->finishSpan($span);
        }

        $hub->addBreadcrumb(new Breadcrumb(
            BreadcrumbType::HTTP,
            "Response {$statusCode} from {$url}",
            category: 'http.outbound',
            level: $statusCode >= 400 ? LogLevel::WARN : LogLevel::INFO,
            data: [
                'method' => $method,
                'url' => $url,
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
            ],
        ));

        return $response;
    }
}
