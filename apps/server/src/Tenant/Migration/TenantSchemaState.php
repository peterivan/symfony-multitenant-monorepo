<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

/**
 * Snapshot of a single tenant database's tenant-migration state, read from the
 * tenant-side version table inside that tenant's database.
 */
final class TenantSchemaState
{
    public function __construct(
        public readonly ?string $currentVersion,
        public readonly int $executedCount,
        public readonly int $pendingCount,
    ) {}

    /**
     * A "newly provisioned" tenant has never had a tenant migration applied.
     */
    public function isNew(): bool
    {
        return 0 === $this->executedCount;
    }
}
