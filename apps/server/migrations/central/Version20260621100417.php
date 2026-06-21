<?php

declare(strict_types=1);

namespace App\Migrations\Central;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the central platform audit table (ADR-002).
 *
 * Append-only audit trail for Back Office operations (tenant lifecycle changes,
 * cross-tenant operations, tenant-owned data access/mutation). Central-owned
 * metadata; runs against the central connection via the central pipeline.
 */
final class Version20260621100417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create central platform_audit_entry table for mandatory Back Office operation auditing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE platform_audit_entry (id UUID NOT NULL, occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, category VARCHAR(255) NOT NULL, operation VARCHAR(191) NOT NULL, outcome VARCHAR(255) NOT NULL, operator_reference VARCHAR(255) DEFAULT NULL, tenant_slug VARCHAR(63) DEFAULT NULL, scope JSONB NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_platform_audit_occurred_at ON platform_audit_entry (occurred_at)');
        $this->addSql('CREATE INDEX idx_platform_audit_tenant_slug ON platform_audit_entry (tenant_slug)');
        // Constrain enum-backed columns to their known value sets at the database level.
        $this->addSql("ALTER TABLE platform_audit_entry ADD CONSTRAINT chk_platform_audit_category CHECK (category IN ('tenant_lifecycle_change', 'cross_tenant_operation', 'tenant_data_access', 'tenant_data_mutation'))");
        $this->addSql("ALTER TABLE platform_audit_entry ADD CONSTRAINT chk_platform_audit_outcome CHECK (outcome IN ('succeeded', 'failed'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE platform_audit_entry DROP CONSTRAINT chk_platform_audit_category');
        $this->addSql('ALTER TABLE platform_audit_entry DROP CONSTRAINT chk_platform_audit_outcome');
        $this->addSql('DROP TABLE platform_audit_entry');
    }
}
