<?php

declare(strict_types=1);

namespace App\Tenant\Registry;

use App\Central\Entity\Tenant;
use App\Central\Repository\TenantRepository;
use App\Tenant\Exception\TenantRegistryLookupException;

/**
 * Doctrine-backed {@see TenantRegistry} reading the registry through the central
 * repository (and therefore the central connection). Wraps lookup failures so the
 * routing layer fails closed rather than leaking infrastructure exceptions.
 */
final class DoctrineTenantRegistry implements TenantRegistry
{
    public function __construct(
        private readonly TenantRepository $repository,
    ) {}

    public function findBySlug(string $slug): ?Tenant
    {
        try {
            return $this->repository->findOneBySlug($slug);
        } catch (\Throwable $e) {
            throw new TenantRegistryLookupException($slug, $e);
        }
    }
}
