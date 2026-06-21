<?php

declare(strict_types=1);

namespace App\Tenant\Boundary;

use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Exception\AmbiguousTenantContextException;
use App\Tenant\Exception\MissingTenantContextException;
use App\Tenant\TenantContext;

/**
 * Asserts the precondition for tenant-scoped work: a resolved, active,
 * unambiguous, establishable tenant context (ADR-003 / ADR-001).
 *
 * This is the single fail-closed gate. Every failure mode is raised as a
 * {@see \App\Tenant\Exception\TenantConnectivityException} and never resolved by
 * falling back to the central database, a default tenant, or stale context.
 */
final class TenantBoundaryGuard
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantEntityManagerProvider $entityManagerProvider,
    ) {}

    /**
     * @param list<string> $candidateSlugs explicit tenant signals (e.g. host,
     *                                      override header) to check for ambiguity
     *
     * @throws \App\Tenant\Exception\TenantConnectivityException fail closed
     */
    public function assertEstablishedTenant(array $candidateSlugs = []): void
    {
        $this->assertUnambiguous($candidateSlugs);

        if (!$this->tenantContext->hasTenant()) {
            throw new MissingTenantContextException();
        }

        // Forces the tenancy routing layer to load the registry entry, verify the
        // tenant is servable, and open its database. Any of those failing throws a
        // fail-closed TenantConnectivityException (not found, not servable,
        // unestablishable) with no central/default/stale fallback.
        $this->entityManagerProvider->getEntityManager();
    }

    /**
     * @param list<string> $candidateSlugs
     */
    private function assertUnambiguous(array $candidateSlugs): void
    {
        $contextSlug = $this->tenantContext->getTenantSlug();

        if (null !== $contextSlug) {
            $candidateSlugs[] = $contextSlug;
        }

        $distinct = array_values(array_unique(array_filter($candidateSlugs, static fn(string $s): bool => '' !== $s)));

        if (\count($distinct) > 1) {
            throw new AmbiguousTenantContextException($distinct);
        }
    }
}
