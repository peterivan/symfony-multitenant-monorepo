<?php

declare(strict_types=1);

use App\Central\Repository\TenantRepository;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\Migration\TenantMigrationOrchestrator;
use App\Tenant\Migration\TenantMigrationStatus;
use App\Tenant\TenantContext;
use App\Tests\Support\FakeTenantSchemaMigrator;
use App\Tests\Support\TenantFactory;

function orchestrator(
    TenantContext $context,
    FakeTenantSchemaMigrator $migrator,
    array $all = [],
    array $bySlug = [],
): array {
    $repository = test()->createMock(TenantRepository::class);
    $repository->method('findAll')->willReturn($all);
    $repository->method('findOneBySlug')->willReturnCallback(static fn(string $slug) => $bySlug[$slug] ?? null);

    return [new TenantMigrationOrchestrator($repository, $context, $migrator), $repository];
}

it('migrates every tenant, establishing context per tenant and disposing it after', function () {
    $context = new TenantContext();
    $migrator = new FakeTenantSchemaMigrator($context);
    [$orchestrator] = orchestrator($context, $migrator, [
        TenantFactory::make('acme'),
        TenantFactory::make('globex'),
    ]);

    $report = $orchestrator->migrateAllTenants();

    expect($report->hasFailures())
        ->toBeFalse()
        ->and($migrator->contextDuringMigrate)
        ->toBe(['acme', 'globex'])
        ->and($context->getTenantSlug())
        ->toBeNull();
});

it('continues past a failed tenant, records it, and reports failures', function () {
    $context = new TenantContext();
    $migrator = new FakeTenantSchemaMigrator($context);
    $migrator->failSlugs = ['globex'];
    [$orchestrator] = orchestrator($context, $migrator, [
        TenantFactory::make('acme'),
        TenantFactory::make('globex'),
        TenantFactory::make('initech'),
    ]);

    $report = $orchestrator->migrateAllTenants();

    expect($report->hasFailures())
        ->toBeTrue()
        ->and($report->failureCount())
        ->toBe(1)
        ->and($report->results[1]->status)
        ->toBe(TenantMigrationStatus::Failed)
        ->and($report->results[2]->status)
        ->toBe(TenantMigrationStatus::Migrated)
        ->and($context->getTenantSlug())
        ->toBeNull();
});

it('fails closed for an unknown single tenant without migrating anything', function () {
    $context = new TenantContext();
    $migrator = new FakeTenantSchemaMigrator($context);
    [$orchestrator] = orchestrator($context, $migrator);

    expect(fn() => $orchestrator->migrateTenant('ghost'))
        ->toThrow(TenantNotFoundException::class)
        ->and($migrator->contextDuringMigrate)
        ->toBe([]);
});

it('migrates only newly provisioned tenants in new-only mode', function () {
    $context = new TenantContext();
    $migrator = new FakeTenantSchemaMigrator($context);
    $migrator->newSlugs = ['fresh'];
    [$orchestrator] = orchestrator($context, $migrator, [
        TenantFactory::make('fresh'),
        TenantFactory::make('existing'),
    ]);

    $report = $orchestrator->migrateNewlyProvisionedTenants();

    expect($migrator->contextDuringMigrate)
        ->toBe(['fresh'])
        ->and($report->results[0]->status)
        ->toBe(TenantMigrationStatus::Migrated)
        ->and($report->results[1]->status)
        ->toBe(TenantMigrationStatus::SkippedNotNew)
        ->and($context->getTenantSlug())
        ->toBeNull();
});

it('disposes tenant context even when every tenant fails', function () {
    $context = new TenantContext();
    $migrator = new FakeTenantSchemaMigrator($context);
    $migrator->failSlugs = ['acme', 'globex'];
    [$orchestrator] = orchestrator($context, $migrator, [
        TenantFactory::make('acme'),
        TenantFactory::make('globex'),
    ]);

    $report = $orchestrator->migrateAllTenants();

    expect($report->failureCount())->toBe(2)->and($context->getTenantSlug())->toBeNull();
});

it('reports per-tenant status and isolates unreachable tenants', function () {
    $context = new TenantContext();
    $migrator = new FakeTenantSchemaMigrator($context);
    $migrator->failSlugs = ['broken'];
    [$orchestrator] = orchestrator($context, $migrator, [
        TenantFactory::make('acme'),
        TenantFactory::make('broken'),
    ]);

    $status = $orchestrator->status();

    expect($status[0]['slug'])
        ->toBe('acme')
        ->and($status[0]['error'])
        ->toBeNull()
        ->and($status[1]['slug'])
        ->toBe('broken')
        ->and($status[1]['error'])
        ->not
        ->toBeNull()
        ->and($context->getTenantSlug())
        ->toBeNull();
});
