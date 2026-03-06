<?php

declare(strict_types=1);

namespace LogTide\WordPress;

use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\Enum\LogLevel;
use LogTide\LogtideSdk;
use LogTide\State\HubInterface;
use LogTide\WordPress\Integration\DatabaseIntegration;
use LogTide\WordPress\Integration\HttpApiIntegration;
use LogTide\WordPress\Integration\WordPressIntegration;

class LogtideWordPress
{
    private static bool $initialized = false;

    private function __construct()
    {
    }

    public static function init(array $config = []): HubInterface
    {
        if (self::$initialized) {
            return LogtideSdk::getCurrentHub();
        }

        $hub = LogtideSdk::init($config);
        self::$initialized = true;

        self::registerHooks($hub);
        self::registerIntegrations($config);

        return $hub;
    }

    private static function registerHooks(HubInterface $hub): void
    {
        add_action('wp_loaded', static function () use ($hub): void {
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::NAVIGATION,
                'WordPress loaded',
                category: 'wordpress.lifecycle',
            ));
        });

        add_action('shutdown', static function () use ($hub): void {
            $hub->flush();
        });

        add_filter('wp_die_handler', static function (callable $handler) use ($hub): callable {
            return static function ($message, $title = '', $args = []) use ($handler, $hub): void {
                if ($message instanceof \WP_Error) {
                    $hub->captureLog(
                        LogLevel::ERROR,
                        $message->get_error_message(),
                        ['code' => $message->get_error_code()],
                    );
                } elseif (is_string($message) && !empty($message)) {
                    $hub->captureLog(LogLevel::ERROR, $message);
                }

                $handler($message, $title, $args);
            };
        });

        add_action('wp_redirect', static function (string $location) use ($hub): string {
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::NAVIGATION,
                "Redirect to {$location}",
                category: 'wordpress.redirect',
                data: ['url' => $location],
            ));
            return $location;
        });

        add_action('wp_mail', static function (array $args) use ($hub): array {
            $to = is_array($args['to']) ? implode(', ', $args['to']) : ($args['to'] ?? '');
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::CUSTOM,
                "Sending email to {$to}",
                category: 'wordpress.mail',
                data: [
                    'to' => $to,
                    'subject' => $args['subject'] ?? '',
                ],
            ));
            return $args;
        });

        add_action('switch_blog', static function (int $newBlogId, int $prevBlogId) use ($hub): void {
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::CUSTOM,
                "Switched blog from {$prevBlogId} to {$newBlogId}",
                category: 'wordpress.multisite',
                data: [
                    'from_blog_id' => $prevBlogId,
                    'to_blog_id' => $newBlogId,
                ],
            ));
        }, 10, 2);

        add_action('activated_plugin', static function (string $plugin) use ($hub): void {
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::CUSTOM,
                "Plugin activated: {$plugin}",
                category: 'wordpress.plugin',
            ));
        });

        add_action('deactivated_plugin', static function (string $plugin) use ($hub): void {
            $hub->addBreadcrumb(new Breadcrumb(
                BreadcrumbType::CUSTOM,
                "Plugin deactivated: {$plugin}",
                category: 'wordpress.plugin',
            ));
        });
    }

    private static function registerIntegrations(array $config): void
    {
        $wpIntegration = new WordPressIntegration();
        $wpIntegration->setupOnce();

        $dbIntegration = new DatabaseIntegration(
            (float) ($config['slow_query_threshold_ms'] ?? 100.0),
        );
        $dbIntegration->setupOnce();

        $httpIntegration = new HttpApiIntegration();
        $httpIntegration->setupOnce();
    }
}
