<?php

declare(strict_types=1);

namespace App\Migrations\Central;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the platform-operator credential column (ADR-007).
 *
 * Stores the hashed platform-authentication credential in central storage,
 * alongside the platform-operator identity. Nullable so existing operators remain
 * valid until a password is set.
 */
final class Version20260621103127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password_hash credential column to platform_operator (platform authentication).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE platform_operator ADD password_hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE platform_operator DROP password_hash');
    }
}
