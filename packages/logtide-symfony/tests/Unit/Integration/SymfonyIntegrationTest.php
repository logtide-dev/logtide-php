<?php

declare(strict_types=1);

namespace LogTide\Symfony\Tests\Unit\Integration;

use LogTide\Client;
use LogTide\Options;
use LogTide\State\Hub;
use LogTide\Symfony\EventSubscriber\ConsoleSubscriber;
use LogTide\Symfony\EventSubscriber\RequestSubscriber;
use LogTide\Symfony\Integration\SymfonyIntegration;
use LogTide\Transport\NullTransport;
use PHPUnit\Framework\TestCase;

final class SymfonyIntegrationTest extends TestCase
{
    private function createHub(): Hub
    {
        return new Hub(new Client(
            Options::fromArray(['default_integrations' => false]),
            new NullTransport(),
        ));
    }

    public function testGetName(): void
    {
        $hub = $this->createHub();
        $integration = new SymfonyIntegration(
            new RequestSubscriber($hub),
            new ConsoleSubscriber($hub),
        );

        $this->assertSame('symfony', $integration->getName());
    }

    public function testSetupOnce(): void
    {
        $hub = $this->createHub();
        $integration = new SymfonyIntegration(
            new RequestSubscriber($hub),
            new ConsoleSubscriber($hub),
        );

        $integration->setupOnce();

        // Should not throw when called twice
        $integration->setupOnce();
        $this->assertTrue(true);
    }

    public function testGetSubscribers(): void
    {
        $hub = $this->createHub();
        $reqSub = new RequestSubscriber($hub);
        $conSub = new ConsoleSubscriber($hub);
        $integration = new SymfonyIntegration($reqSub, $conSub);

        $this->assertSame($reqSub, $integration->getRequestSubscriber());
        $this->assertSame($conSub, $integration->getConsoleSubscriber());
    }

    public function testTeardown(): void
    {
        $hub = $this->createHub();
        $integration = new SymfonyIntegration(
            new RequestSubscriber($hub),
            new ConsoleSubscriber($hub),
        );

        $integration->setupOnce();
        $integration->teardown();

        $this->assertTrue(true);
    }
}
