<?php

declare(strict_types=1);

namespace App\Tenant\Connection;

use App\Central\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates a tenant-bound Doctrine EntityManager (and its underlying DBAL
 * connection) for a single tenant. One call produces resources bound to exactly
 * one tenant database; callers never multiplex across tenants.
 */
interface TenantConnectionFactory
{
    public function createEntityManager(Tenant $tenant): EntityManagerInterface;
}
