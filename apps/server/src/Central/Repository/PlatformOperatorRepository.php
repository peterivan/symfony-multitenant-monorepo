<?php

declare(strict_types=1);

namespace App\Central\Repository;

use App\Central\Entity\PlatformOperator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for the central-owned {@see PlatformOperator} identity.
 *
 * Resolves through the `central` EntityManager because {@see PlatformOperator} is
 * mapped exclusively to it (ADR-006) — there is no tenant path to operator
 * identities.
 *
 * @extends ServiceEntityRepository<PlatformOperator>
 */
class PlatformOperatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlatformOperator::class);
    }

    public function findOneByEmail(string $email): ?PlatformOperator
    {
        return $this->findOneBy(['email' => $email]);
    }
}
