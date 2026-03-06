<?php

declare(strict_types=1);

namespace LogTide\Symfony\EventSubscriber;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Enum\SpanStatus;
use LogTide\State\HubInterface;
use LogTide\Tracing\Span;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleSubscriber implements EventSubscriberInterface
{
    private ?Span $currentSpan = null;

    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 128],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -128],
            ConsoleEvents::ERROR => ['onConsoleError', 0],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $commandName = $command?->getName() ?? 'unknown';

        $this->hub->pushScope();

        $scope = $this->hub->getScope();
        $scope->setTag('console.command', $commandName);

        $this->currentSpan = $this->hub->startSpan('console.command', [
            'description' => $commandName,
        ]);

        if ($this->currentSpan !== null) {
            $this->currentSpan->setAttribute('console.command', $commandName);
        }

        $this->hub->addBreadcrumb(new Breadcrumb(
            type: BreadcrumbType::CONSOLE,
            message: sprintf('Running command: %s', $commandName),
            category: 'console.command',
            level: LogLevel::INFO,
            data: [
                'command' => $commandName,
            ],
        ));
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $exitCode = $event->getExitCode();

        if ($this->currentSpan !== null) {
            $this->currentSpan->setAttribute('console.exit_code', $exitCode);

            $status = $exitCode === 0 ? SpanStatus::OK : SpanStatus::ERROR;
            $this->currentSpan->finish($status);
            $this->hub->finishSpan($this->currentSpan);
            $this->currentSpan = null;
        }

        $this->hub->popScope();
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $error = $event->getError();

        $this->hub->captureException($error);

        if ($this->currentSpan !== null) {
            $this->currentSpan->setAttribute('error', true);
            $this->currentSpan->setAttribute('error.type', get_class($error));
            $this->currentSpan->setAttribute('error.message', $error->getMessage());
        }
    }
}
