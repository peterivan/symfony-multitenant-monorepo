<?php

declare(strict_types=1);

namespace App\Tenant\Migration;

/**
 * Aggregated result of an orchestrated tenant-migration run across one or more
 * tenants.
 */
final class TenantMigrationReport
{
    /**
     * @param list<TenantMigrationResult> $results
     */
    public function __construct(
        public readonly array $results,
    ) {}

    public function hasFailures(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isFailure()) {
                return true;
            }
        }

        return false;
    }

    public function failureCount(): int
    {
        return \count(\array_filter($this->results, static fn(TenantMigrationResult $r): bool => $r->isFailure()));
    }
}
