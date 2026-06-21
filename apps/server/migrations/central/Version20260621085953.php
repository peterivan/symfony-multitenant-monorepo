<?php

declare(strict_types=1);

namespace App\Migrations\Central;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the central tenant registry table (ADR-001).
 *
 * Holds registry, routing, and provisioning metadata only — never tenant-owned
 * business data. Runs against the central connection via the central migrations
 * pipeline. Tenant DB location metadata is stored as the embedded
 * `database_*` columns.
 */
final class Version20260621085953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create central tenant registry table (uuid id, unique slug, tenant DB location metadata, lifecycle state).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tenant (id UUID NOT NULL, slug VARCHAR(63) NOT NULL, lifecycle_state VARCHAR(255) DEFAULT \'active\' NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, database_host VARCHAR(255) NOT NULL, database_port INT NOT NULL, database_name VARCHAR(63) NOT NULL, database_credentials_reference VARCHAR(255) NOT NULL, database_connection_options JSONB NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_tenant_slug ON tenant (slug)');
        // Constrain the lifecycle state to the known value set at the database level.
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT chk_tenant_lifecycle_state CHECK (lifecycle_state IN (\'active\', \'suspended\', \'archived\', \'deleted\'))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant DROP CONSTRAINT chk_tenant_lifecycle_state');
        $this->addSql('DROP TABLE tenant');
    }
}
