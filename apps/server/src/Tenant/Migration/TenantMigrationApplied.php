<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

/**
 * Result of applying tenant migrations to a single tenant database.
 */
final class TenantMigrationApplied
{
    public function __construct(
        public readonly int $appliedCount,
        public readonly TenantSchemaState $state,
    ) {}
}
