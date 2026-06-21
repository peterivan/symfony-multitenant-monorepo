<?php

declare(strict_types=1);

use App\Central\Repository\TenantRepository;
use App\Command\Central\MigrateCentralCommand;
use App\Command\Tenant\MigrateAllTenantsCommand;
use App\Command\Tenant\MigrateTenantCommand;
use App\Command\Tenant\TenantMigrationStatusCommand;
use App\Console\DeclaresExecutionContext;
use App\Console\ExecutionContext;
use App\Console\RequiresExecutionContext;
use App\Tenant\Migration\TenantMigrationOrchestrator;
use App\Tenant\TenantContext;
use App\Tests\Support\FakeTenantSchemaMigrator;
use Doctrine\Migrations\DependencyFactory;

function tenant_command_orchestrator(): TenantMigrationOrchestrator
{
    $context = new TenantContext();

    return new TenantMigrationOrchestrator(
        test()->createMock(TenantRepository::class),
        $context,
        new FakeTenantSchemaMigrator($context),
    );
}

it('declares single-tenant context for the single-tenant migrate command', function () {
    expect(new MigrateTenantCommand(tenant_command_orchestrator())->getExecutionContext())
        ->toBe(ExecutionContext::SingleTenant);
});

it('declares cross-tenant context for the all-tenants migrate command', function () {
    expect(new MigrateAllTenantsCommand(tenant_command_orchestrator())->getExecutionContext())
        ->toBe(ExecutionContext::CrossTenant);
});

it('declares cross-tenant context for the tenant migration status command', function () {
    expect(new TenantMigrationStatusCommand(tenant_command_orchestrator())->getExecutionContext())
        ->toBe(ExecutionContext::CrossTenant);
});

it('declares central context for the central migrate command', function () {
    expect(new MigrateCentralCommand(test()->createMock(DependencyFactory::class))->getExecutionContext())
        ->toBe(ExecutionContext::Central);
});

it('marks every migration command as requiring a declared execution context', function (string $commandClass) {
    expect(is_subclass_of($commandClass, RequiresExecutionContext::class))
        ->toBeTrue()
        ->and(is_subclass_of($commandClass, DeclaresExecutionContext::class))
        ->toBeTrue();
})->with([
    MigrateTenantCommand::class,
    MigrateAllTenantsCommand::class,
    TenantMigrationStatusCommand::class,
    MigrateCentralCommand::class,
]);
