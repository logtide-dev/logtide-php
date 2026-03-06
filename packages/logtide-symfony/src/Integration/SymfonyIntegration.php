<?php

declare(strict_types=1);

namespace LogTide\Symfony\Integration;

use LogTide\Integration\IntegrationInterface;
use LogTide\Symfony\EventSubscriber\ConsoleSubscriber;
use LogTide\Symfony\EventSubscriber\RequestSubscriber;

class SymfonyIntegration implements IntegrationInterface
{
    private bool $registered = false;

    public function __construct(
        private RequestSubscriber $requestSubscriber,
        private ConsoleSubscriber $consoleSubscriber,
    ) {
    }

    public function getName(): string
    {
        return 'symfony';
    }

    public function setupOnce(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
    }

    public function getRequestSubscriber(): RequestSubscriber
    {
        return $this->requestSubscriber;
    }

    public function getConsoleSubscriber(): ConsoleSubscriber
    {
        return $this->consoleSubscriber;
    }

    public function teardown(): void
    {
        $this->registered = false;
    }
}
