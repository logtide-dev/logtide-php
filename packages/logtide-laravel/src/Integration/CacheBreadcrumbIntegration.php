<?php

declare(strict_types=1);

namespace LogTide\Laravel\Integration;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;
use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\State\HubInterface;

class CacheBreadcrumbIntegration
{
    public function __construct(
        private readonly HubInterface $hub,
    ) {
    }

    public function register(): void
    {
        Event::listen(CacheHit::class, function (CacheHit $event): void {
            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::CUSTOM,
                message: "Cache hit: {$event->key}",
                category: 'cache.hit',
                data: [
                    'key' => $event->key,
                    'store' => $event->storeName ?? 'default',
                ],
            ));
        });

        Event::listen(CacheMissed::class, function (CacheMissed $event): void {
            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::CUSTOM,
                message: "Cache miss: {$event->key}",
                category: 'cache.miss',
                data: [
                    'key' => $event->key,
                    'store' => $event->storeName ?? 'default',
                ],
            ));
        });

        Event::listen(KeyWritten::class, function (KeyWritten $event): void {
            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::CUSTOM,
                message: "Cache write: {$event->key}",
                category: 'cache.write',
                data: [
                    'key' => $event->key,
                    'store' => $event->storeName ?? 'default',
                ],
            ));
        });
    }
}
