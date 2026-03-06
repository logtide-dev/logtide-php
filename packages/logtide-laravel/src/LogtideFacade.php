<?php

declare(strict_types=1);

namespace LogTide\Laravel;

use Illuminate\Support\Facades\Facade;
use LogTide\State\HubInterface;

/**
 * @method static \LogTide\ClientInterface|null getClient()
 * @method static \LogTide\State\Scope getScope()
 * @method static \LogTide\State\Scope pushScope()
 * @method static void popScope()
 * @method static mixed withScope(callable $callback)
 * @method static void configureScope(callable $callback)
 * @method static string|null captureEvent(\LogTide\Event $event, ?\LogTide\EventHint $hint = null)
 * @method static string|null captureException(\Throwable $exception)
 * @method static string|null captureLog(\LogTide\Enum\LogLevel $level, string $message, array $metadata = [], ?string $service = null)
 * @method static void addBreadcrumb(\LogTide\Breadcrumb\Breadcrumb $breadcrumb)
 * @method static \LogTide\Tracing\Span|null startSpan(string $operation, array $options = [])
 * @method static void finishSpan(\LogTide\Tracing\Span $span)
 * @method static void flush()
 *
 * @see \LogTide\State\Hub
 */
class LogtideFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HubInterface::class;
    }
}
