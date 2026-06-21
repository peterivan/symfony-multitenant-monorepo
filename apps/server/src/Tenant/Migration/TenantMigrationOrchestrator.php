<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

use App\Central\Entity\Tenant;
use App\Central\Repository\TenantRepository;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\TenantContext;

/**
 * Orchestrates tenant migrations across one tenant, all tenants, or only newly
 * provisioned tenants (ADR-001).
 *
 * Enumerates targets from the central tenant registry, then for each target
 * establishes active tenant context, runs the tenant migration pipeline against
 * that tenant's database, and disposes context — including on the failure path,
 * so no tenant context leaks across iterations. Fails closed per target: a target
 * whose context or tenant database cannot be established is recorded as failed and
 * never falls back to the central database or another tenant.
 */
final class TenantMigrationOrchestrator
{
    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly TenantContext $tenantContext,
        private readonly TenantSchemaMigrator $migrator,
    ) {}

    /**
     * Single-tenant context: migrate exactly one tenant by its slug. Fails closed
     * when the tenant is unknown.
     *
     * @throws TenantNotFoundException when no tenant with this slug exists
     */
    public function migrateTenant(string $slug): TenantMigrationResult
    {
        $tenant = $this->tenants->findOneBySlug($slug);

        if (null === $tenant) {
            throw new TenantNotFoundException($slug);
        }

        return $this->runWithinContext($tenant, $this->applyMigrations(...));
    }

    /**
     * Cross-tenant context: migrate every registered tenant.
     */
    public function migrateAllTenants(): TenantMigrationReport
    {
        return $this->runAcross($this->tenants->findAll(), $this->applyMigrations(...));
    }

    /**
     * Cross-tenant context: migrate only tenants whose tenant database has no
     * applied tenant migrations (newly provisioned).
     */
    public function migrateNewlyProvisionedTenants(): TenantMigrationReport
    {
        return $this->runAcross($this->tenants->findAll(), $this->applyMigrationsIfNew(...));
    }

    /**
     * Provisioning entry point: run tenant migrations for a freshly provisioned
     * tenant (before activation). Same fail-closed, context-scoped semantics.
     */
    public function migrateProvisionedTenant(Tenant $tenant): TenantMigrationResult
    {
        return $this->runWithinContext($tenant, $this->applyMigrations(...));
    }

    /**
     * Cross-tenant context: per-tenant migration status.
     *
     * @return list<array{slug: string, currentVersion: string|null, pendingCount: int, isNew: bool, error: string|null}>
     */
    public function status(): array
    {
        $lines = [];

        foreach ($this->tenants->findAll() as $tenant) {
            $slug = $tenant->getSlug();
            $this->tenantContext->setTenantSlug($slug);

            try {
                $state = $this->migrator->inspect($tenant);
                $lines[] = [
                    'slug' => $slug,
                    'currentVersion' => $state->currentVersion,
                    'pendingCount' => $state->pendingCount,
                    'isNew' => $state->isNew(),
                    'error' => null,
                ];
            } catch (\Throwable $exception) {
                $lines[] = [
                    'slug' => $slug,
                    'currentVersion' => null,
                    'pendingCount' => 0,
                    'isNew' => false,
                    'error' => $exception->getMessage(),
                ];
            } finally {
                $this->tenantContext->reset();
            }
        }

        return $lines;
    }

    /**
     * @param iterable<Tenant>                                  $tenants
     * @param callable(Tenant, string): TenantMigrationResult $operation
     */
    private function runAcross(iterable $tenants, callable $operation): TenantMigrationReport
    {
        $results = [];

        foreach ($tenants as $tenant) {
            $results[] = $this->runWithinContext($tenant, $operation);
        }

        return new TenantMigrationReport($results);
    }

    /**
     * Establish active tenant context, run the operation, and dispose context —
     * including on the failure path, so context never leaks across iterations.
     * Any failure to operate on the tenant is recorded as a failed result.
     *
     * @param callable(Tenant, string): TenantMigrationResult $operation
     */
    private function runWithinContext(Tenant $tenant, callable $operation): TenantMigrationResult
    {
        $slug = $tenant->getSlug();
        $this->tenantContext->setTenantSlug($slug);

        try {
            return $operation($tenant, $slug);
        } catch (\Throwable $exception) {
            return TenantMigrationResult::failed($slug, $exception->getMessage());
        } finally {
            $this->tenantContext->reset();
        }
    }

    private function applyMigrations(Tenant $tenant, string $slug): TenantMigrationResult
    {
        $applied = $this->migrator->migrate($tenant);

        return TenantMigrationResult::migrated($slug, $applied->appliedCount, $applied->state->currentVersion);
    }

    private function applyMigrationsIfNew(Tenant $tenant, string $slug): TenantMigrationResult
    {
        $state = $this->migrator->inspect($tenant);

        if (!$state->isNew()) {
            return TenantMigrationResult::skippedNotNew($slug, $state->currentVersion);
        }

        return $this->applyMigrations($tenant, $slug);
    }
}
