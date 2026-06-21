<?php

declare(strict_types=1);

namespace App\Security\Tenant;

use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;

/**
 * Tenant-context-first gate for tenant authentication (ADR-007).
 *
 * Returns the resolved, servable tenant slug, or fails closed WITHOUT touching any
 * tenant identity store. It enforces tenant-context-first ordering structurally:
 * the tenant user provider calls this before any tenant store access, so a missing,
 * unresolved, ambiguous (the resolver yields no slug for ambiguous hosts), or
 * ineligible (inactive/suspended/archived/deleted) tenant denies authentication
 * before any tenant identity, credential, or tenant authentication state is read.
 *
 * Eligibility is read from the central tenant registry — the same lifecycle signal
 * the routing and boundary layers use — never from the tenant identity store.
 */
final class TenantAuthenticationGate
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantRegistry $tenantRegistry,
    ) {}

    /**
     * @throws TenantAuthenticationUnavailableException when tenant context is
     *                                                   missing/unresolved or the tenant is not eligible
     */
    public function requireServableTenantSlug(): string
    {
        $slug = $this->tenantContext->getTenantSlug();

        if (null === $slug) {
            throw new TenantAuthenticationUnavailableException(
                'Tenant authentication requires a resolved tenant context.',
            );
        }

        $tenant = $this->tenantRegistry->findBySlug($slug);

        if (null === $tenant || !$tenant->getLifecycleState()->isServable()) {
            throw new TenantAuthenticationUnavailableException(
                'The resolved tenant is not eligible for tenant-facing authentication.',
            );
        }

        return $slug;
    }
}
