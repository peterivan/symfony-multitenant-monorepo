<?php

declare(strict_types=1);

use App\Security\Tenant\TenantAuthenticationGate;
use App\Security\Tenant\TenantAuthenticationUnavailableException;
use App\Security\Tenant\TenantUserIdentity;
use App\Security\Tenant\TenantUserProvider;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Entity\TenantUser;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\Repository\TenantUserRepository;
use App\Tenant\TenantContext;
use App\Tests\Support\TenantFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

function servableGate(string $slug): TenantAuthenticationGate
{
    $context = new TenantContext();
    $context->setTenantSlug($slug);

    $registry = test()->createMock(TenantRegistry::class);
    $registry->method('findBySlug')->willReturn(TenantFactory::make($slug));

    return new TenantAuthenticationGate($context, $registry);
}

function emProviderReturning(?TenantUser $user): TenantEntityManagerProvider
{
    $repository = test()->createMock(TenantUserRepository::class);
    $repository->method('findOneByEmail')->willReturn($user);

    $em = test()->createMock(EntityManagerInterface::class);
    $em->method('getRepository')->with(TenantUser::class)->willReturn($repository);

    $provider = test()->createMock(TenantEntityManagerProvider::class);
    $provider->method('getEntityManager')->willReturn($em);

    return $provider;
}

it('is tenant-context-first: fails closed before any tenant store access when context is not servable', function () {
    // The EntityManager provider (the only path to the tenant store) must never be
    // touched when the gate denies — proven by the gate exception surfacing.
    $emProvider = $this->createMock(TenantEntityManagerProvider::class);
    $emProvider->expects($this->never())->method('getEntityManager');

    $gate = new TenantAuthenticationGate(new TenantContext(), $this->createMock(TenantRegistry::class));
    $provider = new TenantUserProvider($gate, $emProvider);

    expect(fn() => $provider->loadUserByIdentifier('user@acme.test'))
        ->toThrow(TenantAuthenticationUnavailableException::class);
});

it('authenticates against the store selected by the resolved tenant and binds state to that tenant', function () {
    $user = new TenantUser('user@acme.test', 'User');
    $provider = new TenantUserProvider(servableGate('acme'), emProviderReturning($user));

    $identity = $provider->loadUserByIdentifier('user@acme.test');

    expect($identity)->toBeInstanceOf(TenantUserIdentity::class)->and($identity->getTenantSlug())->toBe('acme');
});

it('fails closed and final when no matching tenant user exists (no platform fallback)', function () {
    // The provider has no central dependency: a matching email in the central store
    // is never consulted, and the lookup is final for the tenant boundary.
    $provider = new TenantUserProvider(servableGate('acme'), emProviderReturning(null));

    expect(fn() => $provider->loadUserByIdentifier('ghost@acme.test'))->toThrow(UserNotFoundException::class);
});

it('rejects existing tenant state on tenant mismatch, before touching the tenant store', function () {
    $emProvider = $this->createMock(TenantEntityManagerProvider::class);
    $emProvider->expects($this->never())->method('getEntityManager');

    // Resolved context is globex, but the state was bound to acme.
    $provider = new TenantUserProvider(servableGate('globex'), $emProvider);
    $stateBoundToAcme = new TenantUserIdentity(new TenantUser('user@acme.test', 'User'), 'acme');

    expect(fn() => $provider->refreshUser($stateBoundToAcme))->toThrow(TenantAuthenticationUnavailableException::class);
});

it('rejects existing tenant state when the bound tenant has become ineligible', function () {
    $context = new TenantContext();
    $context->setTenantSlug('acme');

    $suspended = TenantFactory::make('acme');
    $suspended->changeLifecycleState(App\Central\Entity\TenantLifecycleState::Suspended);

    $registry = $this->createMock(TenantRegistry::class);
    $registry->method('findBySlug')->willReturn($suspended);

    $emProvider = $this->createMock(TenantEntityManagerProvider::class);
    $emProvider->expects($this->never())->method('getEntityManager');

    $provider = new TenantUserProvider(new TenantAuthenticationGate($context, $registry), $emProvider);
    $state = new TenantUserIdentity(new TenantUser('user@acme.test', 'User'), 'acme');

    expect(fn() => $provider->refreshUser($state))->toThrow(TenantAuthenticationUnavailableException::class);
});

it('refreshes matching tenant state from the resolved tenant store', function () {
    $fresh = new TenantUser('user@acme.test', 'User');
    $provider = new TenantUserProvider(servableGate('acme'), emProviderReturning($fresh));

    $refreshed = $provider->refreshUser(new TenantUserIdentity(new TenantUser('user@acme.test', 'Stale'), 'acme'));

    expect($refreshed)->toBeInstanceOf(TenantUserIdentity::class)->and($refreshed->getTenantSlug())->toBe('acme');
});

it('refuses to refresh a non-tenant user', function () {
    $provider = new TenantUserProvider(servableGate('acme'), emProviderReturning(null));
    $platformUser = new App\Security\Platform\PlatformOperatorUser(new App\Central\Entity\PlatformOperator(
        'e@p.test',
        'E',
    ));

    expect(fn() => $provider->refreshUser($platformUser))->toThrow(UnsupportedUserException::class);
});
