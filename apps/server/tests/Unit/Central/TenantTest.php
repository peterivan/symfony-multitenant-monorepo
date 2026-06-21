<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantDatabaseLocation;
use App\Central\Entity\TenantLifecycleState;
use Symfony\Component\Uid\Uuid;

function tenant(string $slug = 'acme', ?TenantDatabaseLocation $location = null): Tenant
{
    return new Tenant(
        $slug,
        $location ?? new TenantDatabaseLocation('tenant-db', 'tenant_acme', 'secret://tenants/acme/db'),
    );
}

it('generates a globally unique identifier on creation', function () {
    $a = tenant('acme');
    $b = tenant('globex');

    expect($a->getId())->toBeInstanceOf(Uuid::class)->and($a->getId()->equals($b->getId()))->toBeFalse();
});

it('exposes no setter for the immutable identifier', function () {
    expect(method_exists(Tenant::class, 'setId'))->toBeFalse();
});

it('defaults to the active lifecycle state', function () {
    expect(tenant()->getLifecycleState())->toBe(TenantLifecycleState::Active);
});

it('stores database location metadata beyond the database name', function () {
    $location = new TenantDatabaseLocation('tenant-db', 'tenant_acme', 'secret://tenants/acme/db', 6432, [
        'sslmode' => 'require',
    ]);

    expect($location->getHost())
        ->toBe('tenant-db')
        ->and($location->getPort())
        ->toBe(6432)
        ->and($location->getName())
        ->toBe('tenant_acme')
        ->and($location->getConnectionOptions())
        ->toBe(['sslmode' => 'require']);
});

it('references credentials indirectly rather than storing a plaintext password', function () {
    $location = tenant()->getDatabaseLocation();

    expect($location->getCredentialsReference())
        ->toBe('secret://tenants/acme/db')
        ->and(method_exists($location, 'getPassword'))
        ->toBeFalse();
});

it('can transition lifecycle state and update database location', function () {
    $tenant = tenant();

    $tenant->changeLifecycleState(TenantLifecycleState::Suspended);
    $tenant->updateDatabaseLocation(
        new TenantDatabaseLocation('new-host', 'tenant_acme', 'secret://tenants/acme/db', 5544),
    );

    expect($tenant->getLifecycleState())
        ->toBe(TenantLifecycleState::Suspended)
        ->and($tenant->getDatabaseLocation()->getHost())
        ->toBe('new-host')
        ->and($tenant->getDatabaseLocation()->getPort())
        ->toBe(5544);
});

it('treats only the active state as servable', function () {
    expect(TenantLifecycleState::Active->isServable())
        ->toBeTrue()
        ->and(TenantLifecycleState::Suspended->isServable())
        ->toBeFalse()
        ->and(TenantLifecycleState::Archived->isServable())
        ->toBeFalse()
        ->and(TenantLifecycleState::Deleted->isServable())
        ->toBeFalse();
});
