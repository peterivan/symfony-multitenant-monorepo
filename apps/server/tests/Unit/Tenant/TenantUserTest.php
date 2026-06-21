<?php

declare(strict_types=1);

use App\Tenant\Entity\TenantUser;
use Symfony\Component\Uid\Uuid;

it('generates a unique identifier and stores tenant-facing attribution', function () {
    $user = new TenantUser('user@acme.test', 'Acme User');

    expect($user->getId())
        ->toBeInstanceOf(Uuid::class)
        ->and($user->getEmail())
        ->toBe('user@acme.test')
        ->and($user->getDisplayName())
        ->toBe('Acme User');
});

it('keeps name, email and recovery state as tenant-local concerns', function () {
    $user = new TenantUser('user@acme.test', 'Acme User');

    $user->rename('Renamed User');
    $user->changeEmail('renamed@acme.test');
    $user->requestAccountRecovery('recovery-token');

    expect($user->getDisplayName())
        ->toBe('Renamed User')
        ->and($user->getEmail())
        ->toBe('renamed@acme.test')
        ->and($user->getAccountRecoveryToken())
        ->toBe('recovery-token')
        ->and($user->getAccountRecoveryRequestedAt())
        ->toBeInstanceOf(DateTimeImmutable::class);

    $user->clearAccountRecovery();

    expect($user->getAccountRecoveryToken())->toBeNull()->and($user->getAccountRecoveryRequestedAt())->toBeNull();
});

it('carries no Back Office or platform-operator fields (ADR-006)', function () {
    $forbidden = [
        'getBackOfficeAccess',
        'isOperator',
        'getOperator',
        'getPlatformRole',
        'grantBackOffice',
        'getCentralId',
    ];

    foreach ($forbidden as $method) {
        expect(method_exists(TenantUser::class, $method))->toBeFalse("TenantUser must not expose {$method}");
    }
});

it('models the same email in two tenants as two independent identities', function () {
    // Two TenantUser instances stand in for identities in two different tenant
    // databases. They share an email but are independent: distinct ids, no link.
    $inTenantA = new TenantUser('shared@example.test', 'User A');
    $inTenantB = new TenantUser('shared@example.test', 'User B');

    expect($inTenantA->getId()->equals($inTenantB->getId()))
        ->toBeFalse()
        ->and($inTenantA->getEmail())
        ->toBe($inTenantB->getEmail());

    // No field links one to the other or to any cross-tenant identifier.
    $reflection = new ReflectionClass(TenantUser::class);
    foreach ($reflection->getProperties() as $property) {
        $type = $property->getType();
        $name = $type instanceof ReflectionNamedType ? $type->getName() : '';
        expect($name)->not->toContain('PlatformOperator');
    }
});

it('produces a tenant-user-scoped audit reference', function () {
    $user = new TenantUser('user@acme.test', 'Acme User');

    expect($user->auditReference())
        ->toStartWith('tenant-user:')
        ->and($user->auditReference())
        ->toContain($user->getId()->toRfc4122());
});
