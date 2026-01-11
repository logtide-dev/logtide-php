<?php

declare(strict_types=1);

namespace LogTide\SDK;

use LogTide\SDK\Enums\CircuitState;

/**
 * Circuit Breaker pattern implementation
 * Prevents cascading failures by temporarily blocking requests after threshold failures
 */
class CircuitBreaker
{
    private CircuitState $state = CircuitState::CLOSED;
    private int $failureCount = 0;
    private ?float $lastFailureTime = null;

    public function __construct(
        private readonly int $threshold,
        private readonly int $resetMs,
    ) {
    }

    /**
     * Record a successful operation
     * Resets failure count and closes the circuit
     */
    public function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->state = CircuitState::CLOSED;
    }

    /**
     * Record a failed operation
     * Increments failure count and opens circuit if threshold reached
     */
    public function recordFailure(): void
    {
        $this->failureCount++;
        $this->lastFailureTime = microtime(true);

        if ($this->failureCount >= $this->threshold) {
            $this->state = CircuitState::OPEN;
        }
    }

    /**
     * Check if an attempt can be made
     * CLOSED: always allow
     * OPEN: allow after resetMs has elapsed (transition to HALF_OPEN)
     * HALF_OPEN: allow one attempt
     */
    public function canAttempt(): bool
    {
        if ($this->state === CircuitState::CLOSED) {
            return true;
        }

        if ($this->state === CircuitState::OPEN) {
            $now = microtime(true);
            if ($this->lastFailureTime && ($now - $this->lastFailureTime) * 1000 >= $this->resetMs) {
                $this->state = CircuitState::HALF_OPEN;
                return true;
            }
            return false;
        }

        // HALF_OPEN state - allow one attempt
        return true;
    }

    /**
     * Get current circuit state
     */
    public function getState(): CircuitState
    {
        return $this->state;
    }
}
