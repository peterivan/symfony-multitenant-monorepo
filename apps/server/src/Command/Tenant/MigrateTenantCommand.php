<?php

declare(strict_types=1);

namespace App\Command\Tenant;

use App\Console\DeclaresExecutionContext;
use App\Console\ExecutionContext;
use App\Console\RequiresExecutionContext;
use App\Tenant\Exception\TenantNotFoundException;
use App\Tenant\Migration\TenantMigrationOrchestrator;
use App\Tenant\Migration\TenantMigrationStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Single-tenant-context migration command (ADR-001): applies tenant migrations to
 * one tenant's database, identified by slug. Fails closed (non-zero) when the
 * tenant is unknown or its database cannot be migrated; never touches the central
 * database or another tenant.
 */
#[AsCommand(
    name: 'tenant:migrations:migrate',
    description: 'Apply tenant migrations to a single tenant (single-tenant execution context).',
)]
final class MigrateTenantCommand extends Command implements DeclaresExecutionContext, RequiresExecutionContext
{
    public function __construct(
        private readonly TenantMigrationOrchestrator $orchestrator,
    ) {
        parent::__construct();
    }

    public function getExecutionContext(): ExecutionContext
    {
        return ExecutionContext::SingleTenant;
    }

    protected function configure(): void
    {
        $this->addArgument('tenant', InputArgument::REQUIRED, 'The tenant slug to migrate.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $slug */
        $slug = $input->getArgument('tenant');

        try {
            $result = $this->orchestrator->migrateTenant($slug);
        } catch (TenantNotFoundException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if (TenantMigrationStatus::Failed === $result->status) {
            $io->error(\sprintf('Tenant "%s" migration failed: %s', $slug, (string) $result->error));

            return Command::FAILURE;
        }

        $io->success(\sprintf(
            'Tenant "%s": %s (%d applied, version %s).',
            $slug,
            $result->status->value,
            $result->appliedCount,
            $result->currentVersion ?? 'none',
        ));

        return Command::SUCCESS;
    }
}
