<?php

declare(strict_types=1);

namespace LogTide\Laravel;

use Closure;
use Illuminate\Http\Request;
use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Enum\SpanKind;
use LogTide\Enum\SpanStatus;
use LogTide\State\HubInterface;
use LogTide\Tracing\PropagationContext;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LogtideMiddleware
{
    public function __construct(
        private readonly HubInterface $hub,
    ) {
    }

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $path = $request->getPathInfo();
        $skipPaths = config('logtide.skip_paths', ['/health', '/healthz']);

        foreach ($skipPaths as $skip) {
            if (str_starts_with($path, $skip)) {
                return $next($request);
            }
        }

        return $this->hub->withScope(function () use ($request, $next, $path) {
            $scope = $this->hub->getScope();

            $traceparent = $request->header('traceparent');
            if (!empty($traceparent)) {
                $context = PropagationContext::fromTraceparent($traceparent);
                if ($context !== null) {
                    $scope->setPropagationContext($context);
                }
            }

            $method = $request->getMethod();
            $startTime = microtime(true);

            $span = $this->hub->startSpan("HTTP {$method} {$path}", [
                'kind' => SpanKind::SERVER,
            ]);

            $span?->setAttributes([
                'http.method' => $method,
                'http.url' => $request->fullUrl(),
                'http.target' => $path,
                'http.user_agent' => $request->userAgent() ?? '',
            ]);

            $this->hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::HTTP,
                "{$method} {$path}",
                category: 'http.request',
            ));

            try {
                /** @var SymfonyResponse $response */
                $response = $next($request);

                $statusCode = $response->getStatusCode();
                $duration = (microtime(true) - $startTime) * 1000;

                $span?->setAttribute('http.status_code', $statusCode);

                if ($statusCode >= 500) {
                    $span?->setStatus(SpanStatus::ERROR);
                    $this->hub->captureLog(LogLevel::ERROR, "HTTP {$statusCode} {$method} {$path}", [
                        'http.status_code' => $statusCode,
                        'http.duration_ms' => round($duration, 2),
                    ]);
                } else {
                    $span?->setStatus(SpanStatus::OK);
                }

                if ($span !== null) {
                    $this->hub->finishSpan($span);
                }

                $traceparentHeader = $scope->getPropagationContext()->toTraceparent();
                $response->headers->set('traceparent', $traceparentHeader);

                return $response;
            } catch (\Throwable $e) {
                $span?->setStatus(SpanStatus::ERROR, $e->getMessage());
                if ($span !== null) {
                    $this->hub->finishSpan($span);
                }

                $this->hub->captureException($e);
                throw $e;
            }
        });
    }
}
