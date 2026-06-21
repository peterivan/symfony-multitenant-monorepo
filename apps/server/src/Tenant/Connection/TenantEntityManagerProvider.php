<?php

declare(strict_types=1);

namespace App\Tenant\Connection;

use App\Central\Entity\Tenant;
use App\Tenant\Exception\MissingTenantContextException;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\Exception\TenantNotServableException;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The tenancy routing layer: the single path to a tenant-bound Doctrine
 * connection / EntityManager (ADR-001).
 *
 * Keyed strictly by the resolved tenant identifier in {@see TenantContext}.
 * Resolution is lazy (on first request within the execution unit) but always
 * precedes any tenant query, because this is the only way to obtain a tenant
 * EntityManager. Fails closed — with no central/default/stale fallback — when the
 * context is missing, the tenant is unknown, or the tenant is not servable. Binds
 * exactly one tenant to one database and re-resolves when the active tenant
 * changes. Releases resources on reset (between requests / worker reuse).
 */
class TenantEntityManagerProvider implements ResetInterface
{
    private ?string $boundSlug = null;

    private ?EntityManagerInterface $entityManager = null;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantRegistry $tenantRegistry,
        private readonly TenantConnectionFactory $connectionFactory,
    ) {}

    public function getEntityManager(): EntityManagerInterface
    {
        $slug = $this->requireResolvedSlug();

        if (null !== $this->entityManager && $this->boundSlug === $slug && $this->entityManager->isOpen()) {
            return $this->entityManager;
        }

        // First use, the active tenant changed, or the prior manager was closed:
        // dispose any prior tenant resources before opening the next.
        $this->release();

        $tenant = $this->loadServableTenant($slug);
        $entityManager = $this->connectionFactory->createEntityManager($tenant);

        $this->boundSlug = $slug;
        $this->entityManager = $entityManager;

        return $entityManager;
    }

    public function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * Release tenant Doctrine resources for the current execution unit. Safe to
     * call repeatedly and on failure paths; never leaks across execution units.
     */
    public function release(): void
    {
        $entityManager = $this->entityManager;
        $this->entityManager = null;
        $this->boundSlug = null;

        if (null === $entityManager) {
            return;
        }

        $connection = $entityManager->getConnection();

        if ($entityManager->isOpen()) {
            $entityManager->close();
        }

        if ($connection->isConnected()) {
            $connection->close();
        }
    }

    public function reset(): void
    {
        $this->release();
    }

    private function requireResolvedSlug(): string
    {
        $slug = $this->tenantContext->getTenantSlug();

        if (null === $slug) {
            throw new MissingTenantContextException();
        }

        return $slug;
    }

    private function loadServableTenant(string $slug): Tenant
    {
        $tenant = $this->tenantRegistry->findBySlug($slug);

        if (null === $tenant) {
            throw new TenantNotFoundException($slug);
        }

        if (!$tenant->getLifecycleState()->isServable()) {
            throw new TenantNotServableException($slug, $tenant->getLifecycleState());
        }

        return $tenant;
    }
}
