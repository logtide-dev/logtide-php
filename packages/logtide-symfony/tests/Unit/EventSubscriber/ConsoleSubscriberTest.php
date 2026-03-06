<?php

declare(strict_types=1);

namespace LogTide\Symfony\Tests\Unit\EventSubscriber;

use LogTide\Symfony\EventSubscriber\ConsoleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\ConsoleEvents;

final class ConsoleSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = ConsoleSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertArrayHasKey(ConsoleEvents::TERMINATE, $events);
        $this->assertArrayHasKey(ConsoleEvents::ERROR, $events);
    }

    public function testCommandHasHighPriority(): void
    {
        $events = ConsoleSubscriber::getSubscribedEvents();

        $commandConfig = $events[ConsoleEvents::COMMAND];
        $this->assertSame(128, $commandConfig[1]);
    }

    public function testTerminateHasLowPriority(): void
    {
        $events = ConsoleSubscriber::getSubscribedEvents();

        $terminateConfig = $events[ConsoleEvents::TERMINATE];
        $this->assertSame(-128, $terminateConfig[1]);
    }
}
