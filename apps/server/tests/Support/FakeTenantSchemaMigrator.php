<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Central\Entity\Tenant;
use App\Tenant\Migration\TenantMigrationApplied;
use App\Tenant\Migration\TenantSchemaMigrator;
use App\Tenant\Migration\TenantSchemaState;
use App\Tenant\TenantContext;

/**
 * In-memory {@see TenantSchemaMigrator} for orchestrator tests. Records the active
 * tenant context observed at migration time (to prove context establishment) and
 * can be configured to fail or treat tenants as newly provisioned.
 */
final class FakeTenantSchemaMigrator implements TenantSchemaMigrator
{
    /** @var list<string|null> */
    public array $contextDuringMigrate = [];

    /** @var list<string> */
    public array $failSlugs = [];

    /** @var list<string> */
    public array $newSlugs = [];

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function migrate(Tenant $tenant): TenantMigrationApplied
    {
        $this->contextDuringMigrate[] = $this->tenantContext->getTenantSlug();

        if (\in_array($tenant->getSlug(), $this->failSlugs, strict: true)) {
            throw new \RuntimeException('tenant database connection cannot be established');
        }

        return new TenantMigrationApplied(1, new TenantSchemaState('20260101000000', 1, 0));
    }

    public function inspect(Tenant $tenant): TenantSchemaState
    {
        if (\in_array($tenant->getSlug(), $this->failSlugs, strict: true)) {
            throw new \RuntimeException('tenant database connection cannot be established');
        }

        $isNew = \in_array($tenant->getSlug(), $this->newSlugs, strict: true);

        return new TenantSchemaState($isNew ? null : '20260101000000', $isNew ? 0 : 1, 0);
    }
}
