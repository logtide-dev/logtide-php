<?php

declare(strict_types=1);

namespace LogTide\Laravel\Integration;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use LogTide\Breadcrumb\Breadcrumb;
use LogTide\Enum\BreadcrumbType;
use LogTide\State\HubInterface;

class QueryBreadcrumbIntegration
{
    public function __construct(
        private readonly HubInterface $hub,
    ) {
    }

    public function register(): void
    {
        DB::listen(function (QueryExecuted $query): void {
            $this->hub->addBreadcrumb(new Breadcrumb(
                type: BreadcrumbType::QUERY,
                message: $query->sql,
                category: 'db.query',
                data: [
                    'bindings' => $query->bindings,
                    'duration_ms' => $query->time,
                    'connection' => $query->connectionName,
                ],
            ));
        });
    }
}
