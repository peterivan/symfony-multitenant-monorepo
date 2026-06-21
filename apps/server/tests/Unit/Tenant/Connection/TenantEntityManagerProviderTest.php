<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Central\Entity\TenantDatabaseLocation;
use App\Central\Entity\TenantLifecycleState;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Exception\MissingTenantContextException;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\Exception\TenantNotServableException;
use App\Tenant\Exception\TenantRegistryLookupException;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

function servable_tenant(string $slug = 'acme'): Tenant
{
    return new Tenant($slug, new TenantDatabaseLocation('tenant-db', 'tenant_' . $slug, 'secret://' . $slug));
}

/**
 * @param array<string, Tenant> $tenants
 */
function mapped_registry(array $tenants): TenantRegistry
{
    return new class($tenants) implements TenantRegistry {
        /**
         * @param array<string, Tenant> $tenants
         */
        public function __construct(
            private array $tenants,
        ) {}

        public function findBySlug(string $slug): ?Tenant
        {
            return $this->tenants[$slug] ?? null;
        }
    };
}

function throwing_registry(\Throwable $error): TenantRegistry
{
    return new class($error) implements TenantRegistry {
        public function __construct(
            private \Throwable $error,
        ) {}

        public function findBySlug(string $slug): ?Tenant
        {
            throw $this->error;
        }
    };
}

function failing_factory(): TenantConnectionFactory
{
    return new class implements TenantConnectionFactory {
        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            throw new \LogicException('The connection factory must not be reached on a fail-closed path.');
        }
    };
}

/**
 * @param list<EntityManagerInterface> $entityManagers
 */
function recording_factory(array $entityManagers): TenantConnectionFactory
{
    return new class($entityManagers) implements TenantConnectionFactory {
        /** @var list<string> */
        public array $slugs = [];

        /**
         * @param list<EntityManagerInterface> $entityManagers
         */
        public function __construct(
            private array $entityManagers,
        ) {}

        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            $this->slugs[] = $tenant->getSlug();

            return array_shift($this->entityManagers);
        }
    };
}

function context_for(?string $slug): TenantContext
{
    $context = new TenantContext();

    if (null !== $slug) {
        $context->setTenantSlug($slug);
    }

    return $context;
}

it('opens and caches the tenant entity manager for the resolved tenant', function () {
    $em = $this->createMock(EntityManagerInterface::class);
    $em->method('isOpen')->willReturn(true);

    $factory = recording_factory([$em]);
    $provider = new TenantEntityManagerProvider(
        context_for('acme'),
        mapped_registry(['acme' => servable_tenant('acme')]),
        $factory,
    );

    expect($provider->getEntityManager())
        ->toBe($em)
        ->and($provider->getEntityManager())
        ->toBe($em)
        ->and($factory->slugs)
        ->toBe(['acme']);
});

it('fails closed and never calls the factory when no tenant context is resolved', function () {
    $provider = new TenantEntityManagerProvider(context_for(null), mapped_registry([]), failing_factory());
    $provider->getEntityManager();
})->throws(MissingTenantContextException::class);

it('fails closed when the tenant is not in the registry', function () {
    $provider = new TenantEntityManagerProvider(context_for('ghost'), mapped_registry([]), failing_factory());
    $provider->getEntityManager();
})->throws(TenantNotFoundException::class);

it('fails closed when the tenant is not servable', function () {
    $suspended = servable_tenant('acme');
    $suspended->changeLifecycleState(TenantLifecycleState::Suspended);

    $provider = new TenantEntityManagerProvider(
        context_for('acme'),
        mapped_registry(['acme' => $suspended]),
        failing_factory(),
    );
    $provider->getEntityManager();
})->throws(TenantNotServableException::class);

it('propagates registry lookup failures as fail-closed errors with no fallback', function () {
    $registry = throwing_registry(
        new TenantRegistryLookupException('acme', new \RuntimeException('central unreachable')),
    );
    $provider = new TenantEntityManagerProvider(context_for('acme'), $registry, failing_factory());
    $provider->getEntityManager();
})->throws(TenantRegistryLookupException::class);

it('re-resolves and releases the prior manager when the active tenant changes', function () {
    $context = context_for('acme');

    $connection = $this->createMock(Connection::class);
    $connection->method('isConnected')->willReturn(false);

    $first = $this->createMock(EntityManagerInterface::class);
    $first->method('isOpen')->willReturn(true);
    $first->method('getConnection')->willReturn($connection);
    $first->expects($this->once())->method('close');

    $second = $this->createMock(EntityManagerInterface::class);
    $second->method('isOpen')->willReturn(true);

    $factory = recording_factory([$first, $second]);
    $registry = mapped_registry(['acme' => servable_tenant('acme'), 'globex' => servable_tenant('globex')]);
    $provider = new TenantEntityManagerProvider($context, $registry, $factory);

    expect($provider->getEntityManager())->toBe($first);

    $context->setTenantSlug('globex');

    expect($provider->getEntityManager())->toBe($second)->and($factory->slugs)->toBe(['acme', 'globex']);
});

it('returns the connection of the bound tenant manager', function () {
    $connection = $this->createMock(Connection::class);
    $em = $this->createMock(EntityManagerInterface::class);
    $em->method('isOpen')->willReturn(true);
    $em->method('getConnection')->willReturn($connection);

    $provider = new TenantEntityManagerProvider(
        context_for('acme'),
        mapped_registry(['acme' => servable_tenant('acme')]),
        recording_factory([$em]),
    );

    expect($provider->getConnection())->toBe($connection);
});

it('releases tenant resources on reset', function () {
    $connection = $this->createMock(Connection::class);
    $connection->method('isConnected')->willReturn(false);

    $em = $this->createMock(EntityManagerInterface::class);
    $em->method('isOpen')->willReturn(true);
    $em->method('getConnection')->willReturn($connection);
    $em->expects($this->once())->method('close');

    $provider = new TenantEntityManagerProvider(
        context_for('acme'),
        mapped_registry(['acme' => servable_tenant('acme')]),
        recording_factory([$em]),
    );
    $provider->getEntityManager();
    $provider->reset();
});
