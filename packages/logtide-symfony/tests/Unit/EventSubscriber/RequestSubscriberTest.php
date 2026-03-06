<?php

declare(strict_types=1);

namespace LogTide\Symfony\Tests\Unit\EventSubscriber;

use LogTide\Symfony\EventSubscriber\RequestSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = RequestSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }

    public function testRequestHasHighPriority(): void
    {
        $events = RequestSubscriber::getSubscribedEvents();

        $requestConfig = $events[KernelEvents::REQUEST];
        $this->assertSame(256, $requestConfig[1]);
    }
}
