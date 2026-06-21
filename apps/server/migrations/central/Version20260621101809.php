<?php

declare(strict_types=1);

namespace App\Migrations\Central;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621101809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create central platform-operator identity table (ADR-006) and add identity-boundary attribution columns to the platform audit trail.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE platform_operator (id UUID NOT NULL, email VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');
        // Email is unique only within central operator storage (ADR-006): no cross-store/global uniqueness.
        $this->addSql('CREATE UNIQUE INDEX uniq_platform_operator_email ON platform_operator (email)');
        $this->addSql('ALTER TABLE platform_audit_entry ADD identity_boundary VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE platform_audit_entry ADD identity_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE platform_audit_entry ADD platform_operation_scope VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE platform_audit_entry ADD CONSTRAINT chk_platform_audit_identity_boundary CHECK (identity_boundary IS NULL OR identity_boundary IN (\'platform_operator\', \'tenant_user\'))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE platform_audit_entry DROP CONSTRAINT chk_platform_audit_identity_boundary');
        $this->addSql('DROP TABLE platform_operator');
        $this->addSql('ALTER TABLE platform_audit_entry DROP identity_boundary');
        $this->addSql('ALTER TABLE platform_audit_entry DROP identity_reference');
        $this->addSql('ALTER TABLE platform_audit_entry DROP platform_operation_scope');
    }
}
