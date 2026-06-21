<?php

declare(strict_types=1);

namespace App\Migrations\Tenant;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the tenant-user credential column (ADR-007).
 *
 * Runs in the tenant migration pipeline against each tenant's own database, so the
 * hashed tenant-authentication credential lives only inside that tenant's store.
 * Nullable so existing tenant users remain valid until a password is set.
 */
final class Version20260621103300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password_hash credential column to tenant_user (tenant authentication).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant_user ADD password_hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant_user DROP password_hash');
    }
}
