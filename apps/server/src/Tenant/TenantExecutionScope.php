<?php

declare(strict_types=1);

namespace App\Tenant;

use App\Tenant\Connection\TenantEntityManagerProvider;

/**
 * Runs a callable inside a freshly established tenant context and disposes it
 * afterwards — the reusable per-execution-unit establish/dispose primitive
 * (ADR-001).
 *
 * Used by cross-tenant jobs (one scope per tenant iteration) and async consumers
 * to guarantee context is established explicitly, never inherited, and always
 * cleared — including on failure paths — so it cannot leak into the next unit.
 * There is no fallback to a default or stale tenant.
 */
final class TenantExecutionScope
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantEntityManagerProvider $entityManagerProvider,
    ) {}

    /**
     * @template T
     *
     * @param callable():T $work
     *
     * @return T
     */
    public function run(string $slug, callable $work): mixed
    {
        // Start from a clean slate so nothing is inherited from a prior unit.
        $this->dispose();
        $this->tenantContext->setTenantSlug($slug);

        try {
            return $work();
        } finally {
            $this->dispose();
        }
    }

    private function dispose(): void
    {
        $this->tenantContext->reset();
        $this->entityManagerProvider->release();
    }
}
