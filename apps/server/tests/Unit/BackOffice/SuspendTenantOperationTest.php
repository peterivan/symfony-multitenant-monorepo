<?php

declare(strict_types=1);

use App\BackOffice\Operation\AuditedPlatformOperation;
use App\BackOffice\Security\DenyByDefaultPlatformOperatorAuthorizer;
use App\BackOffice\Security\PlatformOperatorAuthorizer;
use App\BackOffice\TenantLifecycle\SuspendTenantOperation;
use App\Central\Entity\AuditCategory;
use App\Central\Entity\AuditOutcome;
use App\Central\Entity\TenantLifecycleState;
use App\Central\Repository\TenantRepository;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\TenantContext;
use App\Tests\Support\TenantFactory;
use Doctrine\ORM\EntityManagerInterface;

function bo_operator_authorizer(): PlatformOperatorAuthorizer
{
    return new class implements PlatformOperatorAuthorizer {
        public function isPlatformOperator(): bool
        {
            return true;
        }

        public function currentOperatorReference(): ?string
        {
            return 'operator:root';
        }
    };
}

it('suspends a known tenant and audits the lifecycle change as a mandatory part', function () {
    $tenant = TenantFactory::make('acme');

    $repository = $this->createMock(TenantRepository::class);
    $repository->method('findOneBySlug')->with('acme')->willReturn($tenant);

    $em = $this->createMock(EntityManagerInterface::class);
    $em->expects($this->once())->method('flush');

    $auditor = bo_spy_auditor();
    $operation = new SuspendTenantOperation(
        $repository,
        $em,
        new AuditedPlatformOperation($auditor, bo_scope(new TenantContext())),
        bo_operator_authorizer(),
    );

    $operation->suspend('acme');

    expect($tenant->getLifecycleState())
        ->toBe(TenantLifecycleState::Suspended)
        ->and($auditor->records)
        ->toHaveCount(1)
        ->and($auditor->records[0][0]->category)
        ->toBe(AuditCategory::TenantLifecycleChange)
        ->and($auditor->records[0][0]->tenantSlug)
        ->toBe('acme')
        ->and($auditor->records[0][0]->operatorReference)
        ->toBe('operator:root')
        ->and($auditor->records[0][1])
        ->toBe(AuditOutcome::Succeeded);
});

it('fails closed for an unknown tenant and changes nothing', function () {
    $repository = $this->createMock(TenantRepository::class);
    $repository->method('findOneBySlug')->willReturn(null);

    $em = $this->createMock(EntityManagerInterface::class);
    $em->expects($this->never())->method('flush');

    $auditor = bo_spy_auditor();
    $operation = new SuspendTenantOperation(
        $repository,
        $em,
        new AuditedPlatformOperation($auditor, bo_scope(new TenantContext())),
        bo_operator_authorizer(),
    );

    expect(fn() => $operation->suspend('ghost'))
        ->toThrow(TenantNotFoundException::class)
        ->and($auditor->records)
        ->toBe([]);
});

it('deny-by-default authorizer denies and attributes no operator', function () {
    $authorizer = new DenyByDefaultPlatformOperatorAuthorizer();

    expect($authorizer->isPlatformOperator())->toBeFalse()->and($authorizer->currentOperatorReference())->toBeNull();
});
