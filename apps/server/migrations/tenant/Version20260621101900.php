<?php

declare(strict_types=1);

namespace App\Migrations\Tenant;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the tenant-user identity table (ADR-006).
 *
 * Runs in the tenant migration pipeline against each tenant's own database, so the
 * email unique index is scoped to a single tenant — the same email may exist
 * independently in other tenants. The table carries tenant-facing attribution and
 * tenant-local profile/account-recovery state only; it holds no platform-operator
 * or Back Office fields and no cross-tenant or central linking identifier.
 */
final class Version20260621101900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenant_user identity table (per-tenant email uniqueness, tenant-local profile/recovery).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tenant_user (id UUID NOT NULL, email VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, account_recovery_token VARCHAR(255) DEFAULT NULL, account_recovery_requested_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_tenant_user_email ON tenant_user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tenant_user');
    }
}
