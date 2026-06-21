<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

use App\Central\Entity\Tenant;

/**
 * Applies and inspects the tenant migration pipeline against a single tenant's
 * database.
 *
 * Implementations open a tenant-bound connection from the tenant's registry
 * location metadata (never the central/default connection), run only the tenant
 * migration namespace, and record applied versions in a tenant-side version table
 * inside that tenant's database — separate from the central migration version
 * table (ADR-001). Any failure to open the tenant database surfaces as a thrown
 * exception so callers fail closed.
 */
interface TenantSchemaMigrator
{
    public function migrate(Tenant $tenant): TenantMigrationApplied;

    public function inspect(Tenant $tenant): TenantSchemaState;
}
