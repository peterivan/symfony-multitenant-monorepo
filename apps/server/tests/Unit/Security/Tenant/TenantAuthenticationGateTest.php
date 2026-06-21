<?php

declare(strict_types=1);

use App\Central\Entity\TenantLifecycleState;
use App\Security\Tenant\TenantAuthenticationGate;
use App\Security\Tenant\TenantAuthenticationUnavailableException;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use App\Tests\Support\TenantFactory;

function gate(TenantContext $context, TenantRegistry $registry): TenantAuthenticationGate
{
    return new TenantAuthenticationGate($context, $registry);
}

it('returns the resolved slug when the tenant context is resolved and servable', function () {
    $context = new TenantContext();
    $context->setTenantSlug('acme');

    $registry = $this->createMock(TenantRegistry::class);
    $registry->method('findBySlug')->with('acme')->willReturn(TenantFactory::make('acme'));

    expect(gate($context, $registry)->requireServableTenantSlug())->toBe('acme');
});

it('fails closed when no tenant context is resolved, without consulting the registry', function () {
    $registry = $this->createMock(TenantRegistry::class);
    $registry->expects($this->never())->method('findBySlug');

    expect(fn() => gate(new TenantContext(), $registry)->requireServableTenantSlug())
        ->toThrow(TenantAuthenticationUnavailableException::class);
});

it('fails closed when the resolved tenant is unknown', function () {
    $context = new TenantContext();
    $context->setTenantSlug('ghost');

    $registry = $this->createMock(TenantRegistry::class);
    $registry->method('findBySlug')->willReturn(null);

    expect(fn() => gate($context, $registry)->requireServableTenantSlug())
        ->toThrow(TenantAuthenticationUnavailableException::class);
});

it('fails closed for every non-servable lifecycle state', function (TenantLifecycleState $state) {
    $context = new TenantContext();
    $context->setTenantSlug('acme');

    $tenant = TenantFactory::make('acme');
    $tenant->changeLifecycleState($state);

    $registry = $this->createMock(TenantRegistry::class);
    $registry->method('findBySlug')->willReturn($tenant);

    expect(fn() => gate($context, $registry)->requireServableTenantSlug())
        ->toThrow(TenantAuthenticationUnavailableException::class);
})->with([
    'suspended' => TenantLifecycleState::Suspended,
    'archived' => TenantLifecycleState::Archived,
    'deleted' => TenantLifecycleState::Deleted,
]);
