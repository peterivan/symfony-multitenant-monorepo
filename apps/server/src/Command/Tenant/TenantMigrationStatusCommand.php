<?php

declare(strict_types=1);

namespace App\Command\Tenant;

use App\Console\DeclaresExecutionContext;
use App\Console\ExecutionContext;
use App\Console\RequiresExecutionContext;
use App\Tenant\Migration\TenantMigrationOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cross-tenant-context status command (ADR-001): reports each tenant's current
 * applied tenant-migration version and whether pending migrations exist. Tenants
 * whose database cannot be reached are reported with an error rather than failing
 * the whole run.
 */
#[AsCommand(
    name: 'tenant:migrations:status',
    description: 'Report tenant migration status across all tenants (cross-tenant execution context).',
)]
final class TenantMigrationStatusCommand extends Command implements DeclaresExecutionContext, RequiresExecutionContext
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];

        foreach ($this->orchestrator->status() as $line) {
            $rows[] = [
                $line['slug'],
                $line['currentVersion'] ?? 'none',
                (string) $line['pendingCount'],
                $line['isNew'] ? 'yes' : 'no',
                $line['error'] ?? '',
            ];
        }

        $io->table(['Tenant', 'Current version', 'Pending', 'New', 'Error'], $rows);

        return Command::SUCCESS;
    }
}
