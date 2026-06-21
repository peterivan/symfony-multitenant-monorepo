<?php

declare(strict_types=1);

namespace App\Tenant\Repository;

use App\Tenant\Entity\TenantUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the tenant-owned {@see TenantUser} identity.
 *
 * Resolves through the `tenant` EntityManager because {@see TenantUser} is mapped
 * exclusively to it (ADR-006). It therefore only ever reads the tenant database
 * selected by the active tenant context — there is no central or cross-tenant
 * path to tenant-user identities.
 *
 * @extends ServiceEntityRepository<TenantUser>
 */
class TenantUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TenantUser::class);
    }

    public function findOneByEmail(string $email): ?TenantUser
    {
        return $this->findOneBy(['email' => $email]);
    }
}
