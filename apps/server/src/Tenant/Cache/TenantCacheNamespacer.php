<?php

declare(strict_types=1);

namespace App\Tenant\Cache;

use App\Tenant\Exception\MissingTenantContextException;
use App\Tenant\TenantContext;

/**
 * Derives a tenant-scoped namespace/key for shared infrastructure (caches,
 * sessions, storage) that holds tenant-owned data, so a single shared store never
 * leaks data across tenants (ADR-001).
 *
 * Fails closed when there is no resolved tenant context: tenant-scoped data must
 * never be written to or read from a shared store without a tenant namespace.
 */
final class TenantCacheNamespacer
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * The namespace for the active tenant, e.g. "tenant.acme".
     */
    public function namespace(): string
    {
        $slug = $this->tenantContext->getTenantSlug();

        if (null === $slug) {
            throw new MissingTenantContextException();
        }

        return 'tenant.' . $slug;
    }

    /**
     * Prefix a cache key with the active tenant namespace, e.g.
     * "tenant.acme.dashboard.summary".
     */
    public function key(string $key): string
    {
        return $this->namespace() . '.' . $key;
    }
}
