<?php

declare(strict_types=1);

namespace App\Console;

/**
 * The execution context a CLI command, worker, or job runs in (ADR-001).
 *
 * ADR-001 requires every such unit to declare whether it runs against the central
 * database, a single tenant, or across all tenants, so tenant-scoped execution is
 * never ambiguous.
 */
enum ExecutionContext: string
{
    case Central = 'central';
    case SingleTenant = 'single-tenant';
    case CrossTenant = 'cross-tenant';

    public function involvesTenantContext(): bool
    {
        return self::Central !== $this;
    }
}
