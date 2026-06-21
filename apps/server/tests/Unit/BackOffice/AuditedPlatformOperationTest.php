<?php

declare(strict_types=1);

use App\BackOffice\Audit\AuditContext;
use App\BackOffice\Audit\PlatformOperationAuditor;
use App\BackOffice\Operation\AuditedPlatformOperation;
use App\Central\Entity\AuditCategory;
use App\Central\Entity\AuditOutcome;
use App\Central\Entity\Tenant;
use App\Tenant\Connection\TenantConnectionFactory;
use App\Tenant\Connection\TenantEntityManagerProvider;
use App\Tenant\Registry\TenantRegistry;
use App\Tenant\TenantContext;
use App\Tenant\TenantExecutionScope;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Spy auditor capturing every record() call.
 */
function bo_spy_auditor(): PlatformOperationAuditor
{
    return new class implements PlatformOperationAuditor {
        /** @var list<array{AuditContext, AuditOutcome}> */
        public array $records = [];

        public function record(AuditContext $context, AuditOutcome $outcome): void
        {
            $this->records[] = [$context, $outcome];
        }
    };
}

function bo_scope(TenantContext $context): TenantExecutionScope
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
            throw new \LogicException('No tenant connection expected.');
        }
    };

    return new TenantExecutionScope($context, new TenantEntityManagerProvider($context, $registry, $factory));
}

function bo_context(AuditCategory $category = AuditCategory::TenantLifecycleChange): AuditContext
{
    return new AuditContext($category, 'op.test', 'operator:root', 'acme', ['k' => 'v']);
}

it('records a success entry when the operation succeeds', function () {
    $auditor = bo_spy_auditor();
    $op = new AuditedPlatformOperation($auditor, bo_scope(new TenantContext()));

    $result = $op->run(bo_context(), static fn(): string => 'done');

    expect($result)
        ->toBe('done')
        ->and($auditor->records)
        ->toHaveCount(1)
        ->and($auditor->records[0][1])
        ->toBe(AuditOutcome::Succeeded);
});

it('records a failure entry and rethrows when the operation throws', function () {
    $auditor = bo_spy_auditor();
    $op = new AuditedPlatformOperation($auditor, bo_scope(new TenantContext()));

    try {
        $op->run(bo_context(), static fn() => throw new \RuntimeException('boom'));
        $this->fail('Expected the operation to rethrow.');
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('boom');
    }

    expect($auditor->records)->toHaveCount(1)->and($auditor->records[0][1])->toBe(AuditOutcome::Failed);
});

it('audits cross-tenant operations under their category', function () {
    $auditor = bo_spy_auditor();
    $op = new AuditedPlatformOperation($auditor, bo_scope(new TenantContext()));

    $op->run(bo_context(AuditCategory::CrossTenantOperation), static fn(): bool => true);

    expect($auditor->records[0][0]->category)->toBe(AuditCategory::CrossTenantOperation);
});

it('exposes no way to skip, defer, or disable auditing', function () {
    $params = new ReflectionMethod(AuditedPlatformOperation::class, 'run')->getParameters();

    expect(array_map(static fn(ReflectionParameter $p): string => $p->getName(), $params))->toBe([
        'context',
        'operation',
    ]);
});

it('runForTenant establishes a bounded tenant context, disposes it, and still audits', function () {
    $context = new TenantContext();
    $auditor = bo_spy_auditor();
    $op = new AuditedPlatformOperation($auditor, bo_scope($context));

    $seen = null;
    $op->runForTenant(bo_context(AuditCategory::TenantDataMutation), 'acme', function () use ($context, &$seen): void {
        $seen = $context->getTenantSlug();
    });

    expect($seen)
        ->toBe('acme') // context established inside the scope
        ->and($context->hasTenant())
        ->toBeFalse() // disposed afterwards
        ->and($auditor->records)
        ->toHaveCount(1)
        ->and($auditor->records[0][1])
        ->toBe(AuditOutcome::Succeeded);
});
