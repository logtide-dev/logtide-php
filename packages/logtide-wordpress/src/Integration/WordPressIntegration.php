<?php

declare(strict_types=1);

namespace LogTide\WordPress\Integration;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\Integration\IntegrationInterface;
use LogTide\LogtideSdk;

class WordPressIntegration implements IntegrationInterface
{
    public function getName(): string
    {
        return 'wordpress';
    }

    public function setupOnce(): void
    {
        $this->registerErrorHandling();
        $this->addRequestBreadcrumbs();
    }

    public function teardown(): void
    {
    }

    private function registerErrorHandling(): void
    {
        add_filter('wp_die_handler', function (callable $handler): callable {
            return function ($message, $title = '', $args = []) use ($handler): void {
                $hub = LogtideSdk::getCurrentHub();

                if ($message instanceof \WP_Error) {
                    $hub->captureLog(
                        LogLevel::ERROR,
                        'wp_die: ' . $message->get_error_message(),
                        [
                            'code' => $message->get_error_code(),
                            'data' => $message->get_error_data(),
                        ],
                    );
                } elseif (is_string($message) && !empty($message)) {
                    $hub->captureLog(LogLevel::ERROR, 'wp_die: ' . $message);
                }

                $handler($message, $title, $args);
            };
        }, 1);

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            $hub = LogtideSdk::getCurrentHub();
            $level = match ($errno) {
                E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => LogLevel::ERROR,
                E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => LogLevel::WARN,
                E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED => LogLevel::INFO,
                default => LogLevel::DEBUG,
            };

            $hub->captureLog($level, $errstr, [
                'file' => $errfile,
                'line' => $errline,
                'errno' => $errno,
            ]);

            return false;
        });
    }

    private function addRequestBreadcrumbs(): void
    {
        $hub = LogtideSdk::getCurrentHub();
        $data = [];

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $data['method'] = $_SERVER['REQUEST_METHOD'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $data['url'] = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $data['host'] = $_SERVER['HTTP_HOST'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $data['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $data['referer'] = $_SERVER['HTTP_REFERER'];
        }

        if (!empty($data)) {
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::HTTP,
                ($data['method'] ?? 'UNKNOWN') . ' ' . ($data['url'] ?? '/'),
                category: 'http.request',
                data: $data,
            ));
        }
    }
}
