<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

/**
 * Per-tenant result of an orchestrated tenant-migration run.
 */
final class TenantMigrationResult
{
    private function __construct(
        public readonly string $slug,
        public readonly TenantMigrationStatus $status,
        public readonly int $appliedCount,
        public readonly ?string $currentVersion,
        public readonly ?string $error,
    ) {}

    public static function migrated(string $slug, int $appliedCount, ?string $currentVersion): self
    {
        $status = $appliedCount > 0 ? TenantMigrationStatus::Migrated : TenantMigrationStatus::AlreadyUpToDate;

        return new self($slug, $status, $appliedCount, $currentVersion, null);
    }

    public static function skippedNotNew(string $slug, ?string $currentVersion): self
    {
        return new self($slug, TenantMigrationStatus::SkippedNotNew, 0, $currentVersion, null);
    }

    public static function failed(string $slug, string $error): self
    {
        return new self($slug, TenantMigrationStatus::Failed, 0, null, $error);
    }

    public function isFailure(): bool
    {
        return TenantMigrationStatus::Failed === $this->status;
    }
}
