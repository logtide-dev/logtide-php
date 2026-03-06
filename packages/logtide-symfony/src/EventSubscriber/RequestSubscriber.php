<?php

declare(strict_types=1);

namespace LogTide\Symfony\EventSubscriber;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Enum\SpanKind;
use LogTide\Enum\SpanStatus;
use LogTide\State\HubInterface;
use LogTide\Tracing\PropagationContext;
use LogTide\Tracing\Span;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $traceparent = $request->headers->get('traceparent');
        if ($traceparent !== null) {
            $propagationContext = PropagationContext::fromTraceparent($traceparent);
            if ($propagationContext !== null) {
                $this->hub->getScope()->setPropagationContext($propagationContext);
            }
        }

        $this->hub->pushScope();

        $scope = $this->hub->getScope();
        $scope->setTag('http.method', $request->getMethod());
        $scope->setTag('http.url', $request->getUri());

        $startTime = microtime(true);
        $request->attributes->set('_logtide_start_time', $startTime);

        $span = $this->hub->startSpan('http.server', [
            'kind' => SpanKind::SERVER,
        ]);

        if ($span !== null) {
            $span->setAttribute('http.method', $request->getMethod());
            $span->setAttribute('http.url', $request->getUri());
            $span->setAttribute('http.target', $request->getPathInfo());
            $span->setAttribute('http.host', $request->getHost());

            if ($request->attributes->get('_route') !== null) {
                $span->setAttribute('http.route', $request->attributes->get('_route'));
            }

            $request->attributes->set('_logtide_span', $span);
        }

        $this->hub->addBreadcrumb(new Breadcrumb(
            type: BreadcrumbType::HTTP,
            message: sprintf('%s %s', $request->getMethod(), $request->getPathInfo()),
            category: 'http.request',
            level: LogLevel::INFO,
            data: [
                'method' => $request->getMethod(),
                'url' => $request->getUri(),
            ],
        ));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        /** @var Span|null $span */
        $span = $request->attributes->get('_logtide_span');
        if ($span !== null) {
            $span->setAttribute('http.status_code', $statusCode);

            $status = $statusCode >= 500 ? SpanStatus::ERROR : SpanStatus::OK;
            $span->finish($status);
            $this->hub->finishSpan($span);
        }

        if ($statusCode >= 500) {
            $this->hub->captureLog(
                LogLevel::ERROR,
                sprintf('HTTP %d on %s %s', $statusCode, $request->getMethod(), $request->getPathInfo()),
                [
                    'http.status_code' => $statusCode,
                    'http.method' => $request->getMethod(),
                    'http.url' => $request->getUri(),
                ],
            );
        }

        $this->hub->popScope();
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $this->hub->captureException($exception);

        /** @var Span|null $span */
        $span = $request->attributes->get('_logtide_span');
        if ($span !== null) {
            $span->setAttribute('error', true);
            $span->setAttribute('error.type', get_class($exception));
            $span->setAttribute('error.message', $exception->getMessage());
            $span->finish(SpanStatus::ERROR, $exception->getMessage());
            $this->hub->finishSpan($span);
        }
    }
}
