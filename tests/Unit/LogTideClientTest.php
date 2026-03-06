<?php

declare(strict_types=1);

namespace LogTide\SDK\Tests\Unit;

use LogTide\SDK\Enums\LogLevel;
use LogTide\SDK\LogTideClient;
use LogTide\SDK\Models\LogEntry;
use LogTide\SDK\Models\LogTideClientOptions;
use PHPUnit\Framework\TestCase;

class LogTideClientTest extends TestCase
{
    private LogTideClient $client;

    protected function setUp(): void
    {
        $this->client = new LogTideClient(new LogTideClientOptions(
            apiUrl: 'http://localhost:8080',
            apiKey: 'test_key',
            batchSize: 5,
            debug: false,
        ));
    }

    public function testLogEntry(): void
    {
        $entry = new LogEntry(
            service: 'test',
            level: LogLevel::INFO,
            message: 'Test message',
        );

        $this->assertNotNull($entry->time);
        $this->assertEquals('test', $entry->service);
        $this->assertEquals(LogLevel::INFO, $entry->level);
    }

    public function testTraceIdContext(): void
    {
        $this->assertNull($this->client->getTraceId());

        // Use valid UUID v4
        $validUuid1 = '550e8400-e29b-41d4-a716-446655440000';
        $validUuid2 = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        $this->client->setTraceId($validUuid1);
        $this->assertEquals($validUuid1, $this->client->getTraceId());

        $result = $this->client->withTraceId($validUuid2, function() {
            return $this->client->getTraceId();
        });

        $this->assertEquals($validUuid2, $result);
        $this->assertEquals($validUuid1, $this->client->getTraceId());
    }

    public function testInvalidTraceIdNormalization(): void
    {
        // Should generate new UUID for invalid trace ID
        $this->client->setTraceId('invalid-trace-id');
        
        $traceId = $this->client->getTraceId();
        $this->assertNotNull($traceId);
        $this->assertNotEquals('invalid-trace-id', $traceId);
        
        // Should be valid UUID format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $traceId
        );
    }

    public function testMetricsInitialization(): void
    {
        $metrics = $this->client->getMetrics();

        $this->assertEquals(0, $metrics->logsSent);
        $this->assertEquals(0, $metrics->logsDropped);
        $this->assertEquals(0, $metrics->errors);
        $this->assertEquals(0, $metrics->retries);
        $this->assertEquals(0.0, $metrics->avgLatencyMs);
    }

    public function testMetricsReset(): void
    {
        $metrics = $this->client->getMetrics();
        $metrics->logsSent = 100;

        $this->client->resetMetrics();
        $newMetrics = $this->client->getMetrics();

        $this->assertEquals(0, $newMetrics->logsSent);
    }
}
