<?php

declare(strict_types=1);

namespace App\Central\Repository;

use App\Central\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the central-owned {@see Tenant} registry.
 *
 * Resolves through the `central` EntityManager because {@see Tenant} is mapped
 * exclusively to it.
 *
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    public function findOneBySlug(string $slug): ?Tenant
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
