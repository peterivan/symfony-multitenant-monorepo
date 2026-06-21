<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

use App\Central\Entity\Tenant;
use App\Tenant\Connection\TenantConnectionFactory;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Drives the tenant migration pipeline through the Doctrine Migrations
 * {@see DependencyFactory} against a per-tenant connection built at runtime from
 * the tenant's registry location metadata (ADR-001).
 *
 * The pipeline is configured with the dedicated tenant migrations directory and
 * namespace and a tenant-side version table, kept entirely separate from the
 * central migration pipeline. The connection is opened from the tenant factory —
 * never the central/default connection — and is closed after each operation.
 */
final class DoctrineTenantSchemaMigrator implements TenantSchemaMigrator
{
    /**
     * @param non-empty-string $tenantMigrationsNamespace
     * @param non-empty-string $tenantMigrationsDir
     * @param non-empty-string $tenantMetadataTable
     */
    public function __construct(
        private readonly TenantConnectionFactory $connectionFactory,
        private readonly string $tenantMigrationsNamespace,
        private readonly string $tenantMigrationsDir,
        private readonly string $tenantMetadataTable,
    ) {}

    public function migrate(Tenant $tenant): TenantMigrationApplied
    {
        $entityManager = $this->connectionFactory->createEntityManager($tenant);
        $connection = $entityManager->getConnection();

        try {
            $dependencyFactory = $this->dependencyFactory($connection);
            $dependencyFactory->getMetadataStorage()->ensureInitialized();

            $available = $dependencyFactory->getMigrationPlanCalculator()->getMigrations();

            if (0 === $available->count()) {
                return new TenantMigrationApplied(0, $this->readState($dependencyFactory));
            }

            $targetVersion = $dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
            $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($targetVersion);
            $appliedCount = \count($plan->getItems());

            if ($appliedCount > 0) {
                $dependencyFactory->getMigrator()->migrate($plan, new MigratorConfiguration()->setAllOrNothing(true));
            }

            return new TenantMigrationApplied($appliedCount, $this->readState($dependencyFactory));
        } finally {
            $this->close($entityManager, $connection);
        }
    }

    public function inspect(Tenant $tenant): TenantSchemaState
    {
        $entityManager = $this->connectionFactory->createEntityManager($tenant);
        $connection = $entityManager->getConnection();

        try {
            return $this->readState($this->dependencyFactory($connection));
        } finally {
            $this->close($entityManager, $connection);
        }
    }

    private function dependencyFactory(Connection $connection): DependencyFactory
    {
        $configuration = new Configuration();
        $configuration->addMigrationsDirectory($this->tenantMigrationsNamespace, $this->tenantMigrationsDir);
        $configuration->setAllOrNothing(true);
        $configuration->setCheckDatabasePlatform(false);

        $storageConfiguration = new TableMetadataStorageConfiguration();
        $storageConfiguration->setTableName($this->tenantMetadataTable);
        $configuration->setMetadataStorageConfiguration($storageConfiguration);

        return DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($connection),
        );
    }

    private function readState(DependencyFactory $dependencyFactory): TenantSchemaState
    {
        $dependencyFactory->getMetadataStorage()->ensureInitialized();

        $executed = $dependencyFactory->getMetadataStorage()->getExecutedMigrations();
        $executedCount = $executed->count();
        $currentVersion = $executedCount > 0 ? (string) $executed->getLast()->getVersion() : null;

        $available = $dependencyFactory->getMigrationPlanCalculator()->getMigrations();
        $pendingCount = 0;

        if ($available->count() > 0) {
            $targetVersion = $dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
            $pendingCount = \count(
                $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($targetVersion)->getItems(),
            );
        }

        return new TenantSchemaState($currentVersion, $executedCount, $pendingCount);
    }

    private function close(EntityManagerInterface $entityManager, Connection $connection): void
    {
        if ($entityManager->isOpen()) {
            $entityManager->close();
        }

        if ($connection->isConnected()) {
            $connection->close();
        }
    }
}
