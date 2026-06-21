<?php

declare(strict_types=1);

use App\Central\Entity\PlatformOperator;
use App\Central\Repository\PlatformOperatorRepository;
use App\Security\Platform\PlatformOperatorUser;
use App\Security\Platform\PlatformUserProvider;
use App\Security\Tenant\TenantUserIdentity;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

function operator(string $email = 'ops@platform.test'): PlatformOperator
{
    $operator = new PlatformOperator($email, 'Ops');
    $operator->setPasswordHash('hashed-secret');

    return $operator;
}

it('carries only the platform-operator role and never a tenant role', function () {
    $user = new PlatformOperatorUser(operator());

    expect($user->getRoles())
        ->toBe(['ROLE_PLATFORM_OPERATOR'])
        ->and($user->getRoles())
        ->not->toContain('ROLE_TENANT_USER')->and($user)
        ->not->toBeInstanceOf(TenantUserIdentity::class);
});

it('exposes the stored credential hash, and null means it cannot authenticate', function () {
    expect(new PlatformOperatorUser(operator())->getPassword())->toBe('hashed-secret');

    $withoutPassword = new PlatformOperatorUser(new PlatformOperator('np@platform.test', 'NP'));
    expect($withoutPassword->getPassword())->toBeNull();
});

it('authenticates platform operators only against the central platform-owned store', function () {
    $operators = $this->createMock(PlatformOperatorRepository::class);
    $operators->expects($this->once())->method('findOneByEmail')->with('ops@platform.test')->willReturn(operator());

    $user = new PlatformUserProvider($operators)->loadUserByIdentifier('ops@platform.test');

    expect($user)->toBeInstanceOf(PlatformOperatorUser::class);
});

it('fails closed and final when no matching operator exists in the central store (no tenant fallback)', function () {
    // The provider has no tenant dependency at all: a matching email in a tenant
    // can never be consulted, and the lookup is final for the platform boundary.
    $operators = $this->createMock(PlatformOperatorRepository::class);
    $operators->method('findOneByEmail')->willReturn(null);

    expect(fn() => new PlatformUserProvider($operators)->loadUserByIdentifier('ghost@platform.test'))
        ->toThrow(UserNotFoundException::class);
});

it('refuses to refresh a non-platform user', function () {
    $operators = $this->createMock(PlatformOperatorRepository::class);
    $provider = new PlatformUserProvider($operators);

    $tenantIdentity = new TenantUserIdentity(new App\Tenant\Entity\TenantUser('e@a.test', 'E'), 'acme');

    expect(fn() => $provider->refreshUser($tenantIdentity))->toThrow(UnsupportedUserException::class);
});

it('supports only the platform operator user class', function () {
    $provider = new PlatformUserProvider($this->createMock(PlatformOperatorRepository::class));

    expect($provider->supportsClass(PlatformOperatorUser::class))
        ->toBeTrue()
        ->and($provider->supportsClass(TenantUserIdentity::class))
        ->toBeFalse();
});
