<?php

declare(strict_types=1);

namespace App\Tenant\Registry;

use App\Central\Entity\Tenant;
use App\Tenant\Exception\TenantRegistryLookupException;

/**
 * Read port over the central tenant registry, used by the tenancy routing layer to
 * resolve a tenant slug to its registry entry (and thereby its database location).
 *
 * Lookups go through the central connection. Infrastructure failures are surfaced
 * as {@see TenantRegistryLookupException} so routing fails closed.
 */
interface TenantRegistry
{
    /**
     * @throws TenantRegistryLookupException when the lookup itself fails
     */
    public function findBySlug(string $slug): ?Tenant;
}
