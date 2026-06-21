<?php

declare(strict_types=1);

namespace App\Command\Central;

use App\Console\DeclaresExecutionContext;
use App\Console\ExecutionContext;
use App\Console\RequiresExecutionContext;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Central-context migration command (ADR-001): runs the central migration pipeline
 * against the central database only. The injected {@see DependencyFactory} is the
 * bundle's default (central) factory.
 */
#[AsCommand(
    name: 'central:migrations:migrate',
    description: 'Apply central database migrations (central execution context).',
)]
final class MigrateCentralCommand extends Command implements DeclaresExecutionContext, RequiresExecutionContext
{
    public function __construct(
        private readonly DependencyFactory $dependencyFactory,
    ) {
        parent::__construct();
    }

    public function getExecutionContext(): ExecutionContext
    {
        return ExecutionContext::Central;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->dependencyFactory->getMetadataStorage()->ensureInitialized();
        $available = $this->dependencyFactory->getMigrationPlanCalculator()->getMigrations();

        if (0 === $available->count()) {
            $io->success('No central migrations to apply.');

            return Command::SUCCESS;
        }

        $targetVersion = $this->dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
        $plan = $this->dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($targetVersion);

        $this->dependencyFactory->getMigrator()->migrate($plan, new MigratorConfiguration()->setAllOrNothing(true));

        $io->success(\sprintf('Central database migrated (%d migration(s) applied).', \count($plan->getItems())));

        return Command::SUCCESS;
    }
}
