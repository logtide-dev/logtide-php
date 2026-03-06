<?php

declare(strict_types=1);

namespace LogTide\Symfony\Tests\Unit\Integration;

use LogTide\Client;
use LogTide\LogtideSdk;
use LogTide\Options;
use LogTide\State\Hub;
use LogTide\Symfony\Integration\DoctrineIntegration;
use LogTide\Transport\NullTransport;
use LogTide\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

final class DoctrineIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        LogtideSdk::reset();
    }

    private function createSpyTransport(): TransportInterface
    {
        return new class implements TransportInterface {
            public array $sentLogs = [];
            public function sendLogs(array $events): void { $this->sentLogs = array_merge($this->sentLogs, $events); }
            public function sendSpans(array $spans): void {}
            public function flush(): void {}
            public function close(): void {}
        };
    }

    private function setupHub(?TransportInterface $transport = null): Hub
    {
        $client = new Client(
            Options::fromArray(['default_integrations' => false]),
            $transport ?? new NullTransport(),
        );
        $hub = new Hub($client);
        LogtideSdk::setCurrentHub($hub);
        return $hub;
    }

    public function testGetName(): void
    {
        $hub = $this->setupHub();
        $integration = new DoctrineIntegration($hub);

        $this->assertSame('doctrine', $integration->getName());
    }

    public function testAddQueryBreadcrumb(): void
    {
        $hub = $this->setupHub();
        $integration = new DoctrineIntegration($hub);

        $integration->addQueryBreadcrumb('SELECT * FROM users', ['id' => 1], 5.3);

        $breadcrumbs = $hub->getScope()->getBreadcrumbs()->getAll();
        $this->assertCount(1, $breadcrumbs);
        $this->assertSame('SELECT * FROM users', $breadcrumbs[0]->message);
        $this->assertSame('db.query', $breadcrumbs[0]->category);
        $this->assertSame('query', $breadcrumbs[0]->type->value);
        $this->assertSame(5.3, $breadcrumbs[0]->data['duration_ms']);
    }

    public function testAddQueryBreadcrumbTruncatesLongSql(): void
    {
        $hub = $this->setupHub();
        $integration = new DoctrineIntegration($hub);

        $longSql = str_repeat('SELECT 1; ', 30); // 300 chars
        $integration->addQueryBreadcrumb($longSql);

        $breadcrumbs = $hub->getScope()->getBreadcrumbs()->getAll();
        $this->assertLessThanOrEqual(203, strlen($breadcrumbs[0]->message)); // 200 + "..."
    }

    public function testAddQueryBreadcrumbSanitizesLongParams(): void
    {
        $hub = $this->setupHub();
        $integration = new DoctrineIntegration($hub);

        $longValue = str_repeat('x', 300);
        $integration->addQueryBreadcrumb('SELECT ?', [$longValue]);

        $breadcrumbs = $hub->getScope()->getBreadcrumbs()->getAll();
        $params = $breadcrumbs[0]->data['params'];
        $this->assertStringContainsString('(truncated)', $params[0]);
        $this->assertLessThan(300, strlen($params[0]));
    }

    public function testCaptureSlowQueryAboveThreshold(): void
    {
        $transport = $this->createSpyTransport();
        $hub = $this->setupHub($transport);
        $integration = new DoctrineIntegration($hub);

        $integration->captureSlowQuery('SELECT SLEEP(2)', 2000.0, 1000.0);

        $this->assertCount(1, $transport->sentLogs);
        $this->assertStringContainsString('Slow query', $transport->sentLogs[0]->getMessage());
        $this->assertSame('warn', $transport->sentLogs[0]->getLevel()->value);
    }

    public function testCaptureSlowQueryBelowThresholdDoesNothing(): void
    {
        $transport = $this->createSpyTransport();
        $hub = $this->setupHub($transport);
        $integration = new DoctrineIntegration($hub);

        $integration->captureSlowQuery('SELECT 1', 50.0, 1000.0);

        $this->assertEmpty($transport->sentLogs);
    }

    public function testAddQueryBreadcrumbWithoutOptionalParams(): void
    {
        $hub = $this->setupHub();
        $integration = new DoctrineIntegration($hub);

        $integration->addQueryBreadcrumb('SELECT 1');

        $breadcrumbs = $hub->getScope()->getBreadcrumbs()->getAll();
        $this->assertCount(1, $breadcrumbs);
        $this->assertArrayNotHasKey('params', $breadcrumbs[0]->data);
        $this->assertArrayNotHasKey('duration_ms', $breadcrumbs[0]->data);
    }

    public function testTeardown(): void
    {
        $hub = $this->setupHub();
        $integration = new DoctrineIntegration($hub);

        $integration->setupOnce();
        $integration->teardown();

        // Should not throw
        $this->assertTrue(true);
    }
}
