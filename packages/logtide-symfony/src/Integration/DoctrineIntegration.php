<?php

declare(strict_types=1);

namespace LogTide\Symfony\Integration;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Integration\IntegrationInterface;
use LogTide\State\HubInterface;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

class DoctrineIntegration implements IntegrationInterface
{
    private bool $registered = false;

    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function getName(): string
    {
        return 'doctrine';
    }

    public function setupOnce(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
    }

    public function addQueryBreadcrumb(
        string $sql,
        ?array $params = null,
        ?float $durationMs = null,
    ): void {
        $data = ['sql' => $sql];

        if ($params !== null) {
            $data['params'] = $this->sanitizeParams($params);
        }

        if ($durationMs !== null) {
            $data['duration_ms'] = round($durationMs, 2);
        }

        $this->hub->addBreadcrumb(new Breadcrumb(
            type: BreadcrumbType::QUERY,
            message: $this->truncateSql($sql),
            category: 'db.query',
            level: LogLevel::DEBUG,
            data: $data,
        ));
    }

    public function captureSlowQuery(
        string $sql,
        float $durationMs,
        float $threshold = 1000.0,
    ): void {
        if ($durationMs < $threshold) {
            return;
        }

        $this->hub->captureLog(
            LogLevel::WARN,
            sprintf('Slow query detected (%.2fms): %s', $durationMs, $this->truncateSql($sql)),
            [
                'db.sql' => $sql,
                'db.duration_ms' => round($durationMs, 2),
                'db.slow_threshold_ms' => $threshold,
            ],
        );
    }

    private function sanitizeParams(array $params): array
    {
        $sanitized = [];
        foreach ($params as $key => $value) {
            if (is_string($value) && strlen($value) > 256) {
                $sanitized[$key] = substr($value, 0, 256) . '... (truncated)';
            } elseif (is_resource($value)) {
                $sanitized[$key] = '[resource]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    private function truncateSql(string $sql, int $maxLength = 200): string
    {
        if (strlen($sql) <= $maxLength) {
            return $sql;
        }
        return substr($sql, 0, $maxLength) . '...';
    }

    public function teardown(): void
    {
        $this->registered = false;
    }
}
