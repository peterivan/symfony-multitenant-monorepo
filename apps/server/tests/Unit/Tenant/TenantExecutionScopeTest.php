<?php

declare(strict_types=1);

use App\Central\Entity\Tenant;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use App\Tenant\TenantExecutionScope;
use Doctrine\ORM\EntityManagerInterface;

function scope_for(TenantContext $context): TenantExecutionScope
{
    $registry = new class implements TenantRegistry {
        public function findBySlug(string $slug): ?Tenant
        {
            return null;
        }
    };

    $factory = new class implements TenantConnectionFactory {
        public function createEntityManager(Tenant $tenant): EntityManagerInterface
        {
            throw new \LogicException('not used');
        }
    };

    return new TenantExecutionScope($context, new TenantEntityManagerProvider($context, $registry, $factory));
}

it('runs the callable inside the established tenant context and returns its value', function () {
    $context = new TenantContext();

    $seen = null;
    $result = scope_for($context)->run('acme', function () use ($context, &$seen) {
        $seen = $context->getTenantSlug();

        return 'done';
    });

    expect($seen)->toBe('acme')->and($result)->toBe('done');
});

it('disposes the tenant context after the unit ends', function () {
    $context = new TenantContext();

    scope_for($context)->run('acme', static fn(): bool => true);

    expect($context->getTenantSlug())->toBeNull();
});

it('disposes the tenant context even when the unit fails', function () {
    $context = new TenantContext();

    $failed = false;

    try {
        scope_for($context)->run('acme', static function (): void {
            throw new \RuntimeException('unit failed');
        });
    } catch (\RuntimeException) {
        $failed = true;
    }

    expect($failed)->toBeTrue()->and($context->getTenantSlug())->toBeNull();
});

it('does not inherit a previous unit context across runs', function () {
    $context = new TenantContext();
    $context->setTenantSlug('stale');

    $seen = null;
    scope_for($context)->run('acme', function () use ($context, &$seen) {
        $seen = $context->getTenantSlug();
    });

    expect($seen)->toBe('acme')->and($context->getTenantSlug())->toBeNull();
});
