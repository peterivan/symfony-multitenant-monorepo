<?php

declare(strict_types=1);

use App\Central\Entity\PlatformOperator;
use Symfony\Component\Uid\Uuid;

it('generates a unique identifier and stores platform attribution', function () {
    $a = new PlatformOperator('ops@platform.test', 'Ops One');
    $b = new PlatformOperator('ops@platform.test', 'Ops Two');

    expect($a->getId())
        ->toBeInstanceOf(Uuid::class)
        ->and($a->getEmail())
        ->toBe('ops@platform.test')
        ->and($a->getDisplayName())
        ->toBe('Ops One')
        ->and($a->getId()->equals($b->getId()))
        ->toBeFalse();
});

it('carries no tenant-user or tenant-access fields (ADR-006)', function () {
    $forbidden = [
        'getTenantSlug',
        'getTenant',
        'getTenantId',
        'getTenantUser',
        'getRoles',
        'getMemberships',
        'getTenantAccess',
    ];

    foreach ($forbidden as $method) {
        expect(method_exists(PlatformOperator::class, $method))
            ->toBeFalse("PlatformOperator must not expose {$method}");
    }
});

it('does not reference any tenant-user identity', function () {
    $reflection = new ReflectionClass(PlatformOperator::class);

    foreach ($reflection->getProperties() as $property) {
        $type = $property->getType();
        $name = $type instanceof ReflectionNamedType ? $type->getName() : '';
        expect($name)->not->toContain('Tenant');
    }
});

it('produces an operator-scoped audit reference', function () {
    $operator = new PlatformOperator('ops@platform.test', 'Ops');

    expect($operator->auditReference())
        ->toStartWith('operator:')
        ->and($operator->auditReference())
        ->toContain($operator->getId()->toRfc4122());
});
