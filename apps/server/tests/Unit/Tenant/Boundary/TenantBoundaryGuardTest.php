<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantLifecycleState;
use App\Tenant\Boundary\TenantBoundaryGuard;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Exception\AmbiguousTenantContextException;
use App\Tenant\Exception\MissingTenantContextException;
use App\Tenant\Exception\TenantConnectivityException;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\Exception\TenantNotServableException;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use App\Tests\Support\TenantFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @param array<string, Tenant> $tenants
 */
function bg_registry(array $tenants): TenantRegistry
{
    return new class($tenants) implements TenantRegistry {
        /** @param array<string, Tenant> $tenants */
        public function __construct(
            private array $tenants,
        ) {}

        public function findBySlug(string $slug): ?Tenant
        {
            return $this->tenants[$slug] ?? null;
        }
    };
}

function bg_factory(EntityManagerInterface $entityManager): TenantConnectionFactory
{
    return new class($entityManager) implements TenantConnectionFactory {
        public function __construct(
            private EntityManagerInterface $entityManager,
        ) {}

        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            return $this->entityManager;
        }
    };
}

function bg_unreached_factory(): TenantConnectionFactory
{
    return new class implements TenantConnectionFactory {
        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            throw new \LogicException('Factory must not be reached on a fail-closed path.');
        }
    };
}

function bg_context(?string $slug): TenantContext
{
    $context = new TenantContext();

    if (null !== $slug) {
        $context->setTenantSlug($slug);
    }

    return $context;
}

function bg_guard(
    TenantContext $context,
    TenantRegistry $registry,
    TenantConnectionFactory $factory,
): TenantBoundaryGuard {
    return new TenantBoundaryGuard($context, new TenantEntityManagerProvider($context, $registry, $factory));
}

it('passes for a resolved, active, establishable tenant', function () {
    $em = $this->createMock(EntityManagerInterface::class);
    $context = bg_context('acme');

    bg_guard(
        $context,
        bg_registry(['acme' => TenantFactory::make('acme')]),
        bg_factory($em),
    )->assertEstablishedTenant();

    expect(true)->toBeTrue(); // reached only if no exception thrown
});

it('fails closed when no tenant context is resolved', function () {
    bg_guard(bg_context(null), bg_registry([]), bg_unreached_factory())->assertEstablishedTenant();
})->throws(MissingTenantContextException::class);

it('fails closed when the resolved tenant is unknown', function () {
    bg_guard(bg_context('ghost'), bg_registry([]), bg_unreached_factory())->assertEstablishedTenant();
})->throws(TenantNotFoundException::class);

it('fails closed when the resolved tenant is inactive', function () {
    $suspended = TenantFactory::make('acme');
    $suspended->changeLifecycleState(TenantLifecycleState::Suspended);

    bg_guard(
        bg_context('acme'),
        bg_registry(['acme' => $suspended]),
        bg_unreached_factory(),
    )->assertEstablishedTenant();
})->throws(TenantNotServableException::class);

it('fails closed when the tenant database cannot be established', function () {
    $factory = new class implements TenantConnectionFactory {
        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            throw new class('boom') extends TenantConnectivityException {};
        }
    };

    bg_guard(
        bg_context('acme'),
        bg_registry(['acme' => TenantFactory::make('acme')]),
        $factory,
    )->assertEstablishedTenant();
})->throws(TenantConnectivityException::class);

it('fails closed when tenant signals are ambiguous, before touching the registry', function () {
    bg_guard(bg_context('acme'), bg_registry([]), bg_unreached_factory())->assertEstablishedTenant(['globex']);
})->throws(AmbiguousTenantContextException::class);

it('does not consider a single repeated tenant signal ambiguous', function () {
    $em = $this->createMock(EntityManagerInterface::class);

    bg_guard(
        bg_context('acme'),
        bg_registry(['acme' => TenantFactory::make('acme')]),
        bg_factory($em),
    )->assertEstablishedTenant(['acme']);

    expect(true)->toBeTrue();
});
