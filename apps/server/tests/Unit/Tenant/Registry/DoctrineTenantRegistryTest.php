<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantDatabaseLocation;
use App\Central\Repository\TenantRepository;
use App\Tenant\Exception\TenantRegistryLookupException;
use App\Tenant\Registry\DoctrineTenantRegistry;

it('returns the registry entry for a known slug', function () {
    $tenant = new Tenant('acme', new TenantDatabaseLocation('tenant-db', 'tenant_acme', 'secret://acme'));

    $repository = $this->createMock(TenantRepository::class);
    $repository->method('findOneBySlug')->with('acme')->willReturn($tenant);

    expect(new DoctrineTenantRegistry($repository)->findBySlug('acme'))->toBe($tenant);
});

it('returns null for an unknown slug', function () {
    $repository = $this->createMock(TenantRepository::class);
    $repository->method('findOneBySlug')->willReturn(null);

    expect(new DoctrineTenantRegistry($repository)->findBySlug('ghost'))->toBeNull();
});

it('wraps lookup infrastructure failures as fail-closed errors', function () {
    $repository = $this->createMock(TenantRepository::class);
    $repository->method('findOneBySlug')->willThrowException(new \RuntimeException('central unreachable'));

    new DoctrineTenantRegistry($repository)->findBySlug('acme');
})->throws(TenantRegistryLookupException::class);
