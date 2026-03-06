<?php

declare(strict_types=1);

namespace LogTide\WordPress\Integration;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Integration\IntegrationInterface;
use LogTide\LogtideSdk;

class DatabaseIntegration implements IntegrationInterface
{
    public function __construct(
        private readonly float $slowQueryThresholdMs = 100.0,
    ) {
    }

    public function getName(): string
    {
        return 'wordpress.database';
    }

    public function setupOnce(): void
    {
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return;
        }

        add_action('shutdown', [$this, 'processQueries'], 1);
    }

    public function teardown(): void
    {
    }

    public function processQueries(): void
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        if (!isset($wpdb->queries) || !is_array($wpdb->queries)) {
            return;
        }

        $hub = LogtideSdk::getCurrentHub();

        foreach ($wpdb->queries as $query) {
            [$sql, $elapsed, $caller] = $query;
            $durationMs = $elapsed * 1000;

            if ($durationMs < $this->slowQueryThresholdMs) {
                continue;
            }

            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::QUERY,
                $this->truncateSql($sql),
                category: 'db.query',
                level: $durationMs >= ($this->slowQueryThresholdMs * 5) ? LogLevel::WARN : LogLevel::INFO,
                data: [
                    'duration_ms' => round($durationMs, 2),
                    'caller' => $caller,
                ],
            ));
        }
    }

    private function truncateSql(string $sql, int $maxLength = 500): string
    {
        $sql = trim(preg_replace('/\s+/', ' ', $sql));

        if (strlen($sql) > $maxLength) {
            return substr($sql, 0, $maxLength) . '...';
        }

        return $sql;
    }
}
