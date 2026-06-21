<?php

declare(strict_types=1);

namespace App\Migrations\Tenant;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baseline tenant-database migration (ADR-001).
 *
 * Lives in the dedicated tenant migration namespace/directory, separate from the
 * central pipeline, and runs against each tenant's own database. Tenant migrations
 * may use PostgreSQL-specific features (ADR-005); this baseline enables the
 * pgcrypto extension that tenant schemas can rely on.
 */
final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline tenant database: enable pgcrypto extension.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pgcrypto');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION IF EXISTS pgcrypto');
    }
}
