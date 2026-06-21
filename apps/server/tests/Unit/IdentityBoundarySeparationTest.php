<?php

declare(strict_types=1);

use App\Central\Entity\PlatformOperator;
use App\Tenant\Entity\TenantUser;
use Doctrine\ORM\Mapping as ORM;

it('owns each identity in its own mapping boundary (ADR-006)', function () {
    // Platform operators live in the central boundary; tenant users in the tenant
    // boundary. The namespace is the mapping boundary (see doctrine.yaml mappings).
    expect(PlatformOperator::class)
        ->toStartWith('App\\Central\\Entity\\')
        ->and(TenantUser::class)
        ->toStartWith('App\\Tenant\\Entity\\');
});

it('enforces email uniqueness only within each store, never across stores', function (string $class) {
    $reflection = new ReflectionClass($class);
    $emailColumn = $reflection->getProperty('email')->getAttributes(ORM\Column::class);

    expect($emailColumn)->toHaveCount(1)->and($emailColumn[0]->newInstance()->unique)->toBeTrue();
})->with([
    PlatformOperator::class,
    TenantUser::class,
]);

it('defines no shared person/account identifier across the two boundaries', function () {
    $operatorProps = array_map(
        static fn(ReflectionProperty $p): string => $p->getName(),
        new ReflectionClass(PlatformOperator::class)->getProperties(),
    );
    $userProps = array_map(
        static fn(ReflectionProperty $p): string => $p->getName(),
        new ReflectionClass(TenantUser::class)->getProperties(),
    );

    // Neither identity carries a personId/accountId/cross-boundary link.
    foreach (['personId', 'accountId', 'globalId', 'tenantUserId', 'operatorId'] as $linking) {
        expect($operatorProps)->not->toContain($linking)->and($userProps)->not->toContain($linking);
    }
});

it('introduces no central tenant-membership model (ADR-006)', function () {
    $centralEntities = glob(__DIR__ . '/../../src/Central/Entity/*.php');

    foreach ($centralEntities as $file) {
        expect(strtolower(basename($file)))->not->toContain('membership')->not->toContain('tenantuser');
    }
});
