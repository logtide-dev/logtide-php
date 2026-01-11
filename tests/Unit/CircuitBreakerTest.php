<?php

declare(strict_types=1);

namespace LogTide\SDK\Tests\Unit;

use LogTide\SDK\CircuitBreaker;
use LogTide\SDK\Enums\CircuitState;
use PHPUnit\Framework\TestCase;

class CircuitBreakerTest extends TestCase
{
    public function testInitialStateClosed(): void
    {
        $cb = new CircuitBreaker(threshold: 3, resetMs: 1000);
        
        $this->assertEquals(CircuitState::CLOSED, $cb->getState());
        $this->assertTrue($cb->canAttempt());
    }

    public function testOpenAfterThresholdFailures(): void
    {
        $cb = new CircuitBreaker(threshold: 3, resetMs: 1000);
        
        $cb->recordFailure();
        $cb->recordFailure();
        $this->assertEquals(CircuitState::CLOSED, $cb->getState());
        
        $cb->recordFailure();
        $this->assertEquals(CircuitState::OPEN, $cb->getState());
        $this->assertFalse($cb->canAttempt());
    }

    public function testHalfOpenAfterResetTime(): void
    {
        $cb = new CircuitBreaker(threshold: 2, resetMs: 100);
        
        // Open the circuit
        $cb->recordFailure();
        $cb->recordFailure();
        $this->assertEquals(CircuitState::OPEN, $cb->getState());
        
        // Wait for reset time
        usleep(150 * 1000); // 150ms
        
        // Should transition to HALF_OPEN and allow attempt
        $this->assertTrue($cb->canAttempt());
        $this->assertEquals(CircuitState::HALF_OPEN, $cb->getState());
    }

    public function testSuccessResetsFailureCount(): void
    {
        $cb = new CircuitBreaker(threshold: 3, resetMs: 1000);
        
        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordSuccess();
        
        // Should be closed again
        $this->assertEquals(CircuitState::CLOSED, $cb->getState());
        
        // Should require 3 more failures to open
        $cb->recordFailure();
        $cb->recordFailure();
        $this->assertEquals(CircuitState::CLOSED, $cb->getState());
    }
}
