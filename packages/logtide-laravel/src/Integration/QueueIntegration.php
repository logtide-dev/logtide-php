<?php

declare(strict_types=1);

namespace LogTide\Laravel\Integration;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Enum\SpanKind;
use LogTide\Enum\SpanStatus;
use LogTide\State\HubInterface;
use LogTide\Tracing\Span;
use WeakMap;

class QueueIntegration
{
    /** @var WeakMap<object, Span> */
    private WeakMap $jobSpans;

    public function __construct(
        private readonly HubInterface $hub,
    ) {
        $this->jobSpans = new WeakMap();
    }

    public function register(): void
    {
        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $jobName = $event->job->resolveName();

            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::CUSTOM,
                message: "Processing job: {$jobName}",
                category: 'queue.process',
                data: [
                    'job' => $jobName,
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                ],
            ));

            $span = $this->hub->startSpan("queue.process {$jobName}", [
                'kind' => SpanKind::CONSUMER,
            ]);

            $span?->setAttributes([
                'queue.job' => $jobName,
                'queue.name' => $event->job->getQueue(),
                'queue.connection' => $event->connectionName,
            ]);

            if ($span !== null) {
                $this->jobSpans[$event->job] = $span;
            }
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event): void {
            $jobName = $event->job->resolveName();

            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::CUSTOM,
                message: "Processed job: {$jobName}",
                category: 'queue.processed',
                data: [
                    'job' => $jobName,
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                ],
            ));

            if (isset($this->jobSpans[$event->job])) {
                $span = $this->jobSpans[$event->job];
                $span->setStatus(SpanStatus::OK);
                $this->hub->finishSpan($span);
                unset($this->jobSpans[$event->job]);
            }
        });

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            $jobName = $event->job->resolveName();

            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::CUSTOM,
                message: "Failed job: {$jobName}",
                category: 'queue.failed',
                level: LogLevel::ERROR,
                data: [
                    'job' => $jobName,
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                    'exception' => $event->exception->getMessage(),
                ],
            ));

            if (isset($this->jobSpans[$event->job])) {
                $span = $this->jobSpans[$event->job];
                $span->setStatus(SpanStatus::ERROR, $event->exception->getMessage());
                $this->hub->finishSpan($span);
                unset($this->jobSpans[$event->job]);
            }

            $this->hub->captureException($event->exception);
        });
    }
}
