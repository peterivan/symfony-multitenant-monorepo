<?php

declare(strict_types=1);

namespace App\BackOffice\TenantLifecycle;

use App\BackOffice\Audit\AuditContext;
use App\BackOffice\Operation\AuditedPlatformOperation;
use App\BackOffice\Security\PlatformOperatorAuthorizer;
use App\Central\Entity\AuditCategory;
use App\Central\Entity\Tenant;
use App\Central\Entity\TenantLifecycleState;
use App\Central\Repository\TenantRepository;
use App\Tenant\Exception\TenantNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Suspends a tenant from the Back Office (ADR-002).
 *
 * A tenant lifecycle change: it acts on the CENTRAL registry, so it runs in central
 * context and never establishes tenant context. It is performed through
 * {@see AuditedPlatformOperation}, so the change is audited as a mandatory part of
 * the operation. Fails closed (no audit of a non-event) when the tenant is unknown.
 */
final class SuspendTenantOperation
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly EntityManagerInterface $centralEntityManager,
        private readonly AuditedPlatformOperation $auditedOperation,
        private readonly PlatformOperatorAuthorizer $authorizer,
    ) {}

    /**
     * @throws TenantNotFoundException when no tenant matches the slug
     */
    public function suspend(string $slug): void
    {
        $tenant = $this->tenantRepository->findOneBySlug($slug);

        if (!$tenant instanceof Tenant) {
            throw new TenantNotFoundException($slug);
        }

        $context = new AuditContext(
            AuditCategory::TenantLifecycleChange,
            'tenant.suspend',
            $this->authorizer->currentOperatorReference(),
            $slug,
            ['from' => $tenant->getLifecycleState()->value, 'to' => TenantLifecycleState::Suspended->value],
        );

        $this->auditedOperation->run($context, function () use ($tenant): void {
            $tenant->changeLifecycleState(TenantLifecycleState::Suspended);
            $this->centralEntityManager->flush();
        });
    }
}
