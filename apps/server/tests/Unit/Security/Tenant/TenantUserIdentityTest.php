<?php

declare(strict_types=1);

use App\Security\Platform\PlatformOperatorUser;
use App\Security\Tenant\TenantUserIdentity;
use App\Tenant\Entity\TenantUser;

function tenantIdentity(string $slug = 'acme'): TenantUserIdentity
{
    $user = new TenantUser('user@' . $slug . '.test', 'User');
    $user->setPasswordHash('hashed-secret');

    return new TenantUserIdentity($user, $slug);
}

it('carries only the tenant-user role and never a platform role', function () {
    $identity = tenantIdentity();

    expect($identity->getRoles())
        ->toBe(['ROLE_TENANT_USER'])
        ->and($identity->getRoles())
        ->not->toContain('ROLE_PLATFORM_OPERATOR')->and($identity)
        ->not->toBeInstanceOf(PlatformOperatorUser::class);
});

it('is bound to the tenant it authenticated in', function () {
    expect(tenantIdentity('globex')->getTenantSlug())->toBe('globex');
});

it('exposes the stored credential hash', function () {
    expect(tenantIdentity()->getPassword())->toBe('hashed-secret');
});
