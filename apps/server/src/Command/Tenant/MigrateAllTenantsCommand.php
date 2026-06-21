<?php

declare(strict_types=1);

namespace App\Command\Tenant;

use App\Console\DeclaresExecutionContext;
use App\Console\ExecutionContext;
use App\Console\RequiresExecutionContext;
use App\Tenant\Migration\TenantMigrationOrchestrator;
use App\Tenant\Migration\TenantMigrationReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cross-tenant-context migration command (ADR-001): applies tenant migrations
 * across all registered tenants, or only newly provisioned tenants with
 * --new-only. Each tenant is migrated in its own established-then-disposed
 * context; a failed tenant is recorded and the run continues, exiting non-zero if
 * any tenant failed. Never falls back to the central database or a default tenant.
 */
#[AsCommand(
    name: 'tenant:migrations:migrate-all',
    description: 'Apply tenant migrations across all (or only new) tenants (cross-tenant execution context).',
)]
final class MigrateAllTenantsCommand extends Command implements DeclaresExecutionContext, RequiresExecutionContext
{
    public function __construct(
        private readonly TenantMigrationOrchestrator $orchestrator,
    ) {
        parent::__construct();
    }

    public function getExecutionContext(): ExecutionContext
    {
        return ExecutionContext::CrossTenant;
    }

    protected function configure(): void
    {
        $this->addOption(
            'new-only',
            null,
            InputOption::VALUE_NONE,
            'Migrate only newly provisioned tenants (those with no applied tenant migrations).',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $newOnly = true === $input->getOption('new-only');

        $report = $newOnly
            ? $this->orchestrator->migrateNewlyProvisionedTenants()
            : $this->orchestrator->migrateAllTenants();

        $this->renderReport($io, $report);

        if ($report->hasFailures()) {
            $io->error(\sprintf('%d tenant(s) failed to migrate.', $report->failureCount()));

            return Command::FAILURE;
        }

        $io->success(\sprintf('All %d tenant(s) processed successfully.', \count($report->results)));

        return Command::SUCCESS;
    }

    private function renderReport(SymfonyStyle $io, TenantMigrationReport $report): void
    {
        $rows = [];

        foreach ($report->results as $result) {
            $rows[] = [
                $result->slug,
                $result->status->value,
                (string) $result->appliedCount,
                $result->currentVersion ?? '-',
                $result->error ?? '',
            ];
        }

        $io->table(['Tenant', 'Status', 'Applied', 'Version', 'Error'], $rows);
    }
}
