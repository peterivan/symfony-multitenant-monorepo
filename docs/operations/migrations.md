# Database Migrations

Two **separate** migration pipelines exist (ADR-001): one for the central database
(tenant registry / routing / provisioning metadata) and one for tenant databases.
They have distinct directories, namespaces, and version-tracking tables and never
mix. Every migration command declares its execution context (central /
single-tenant / cross-tenant); a command that fails to declare one is rejected
before it runs.

All commands run inside the Docker `app` container, from the repository root.

## Central pipeline (central execution context)

- Files: `apps/server/migrations/central/` (namespace `App\Migrations\Central`)
- Version table: the bundle default, in the **central** database.

```bash
# Apply central migrations (bundle command, central database):
docker compose exec app php bin/console doctrine:migrations:migrate

# Equivalent context-declared wrapper:
docker compose exec app php bin/console central:migrations:migrate
```

## Tenant pipeline (single-tenant / cross-tenant execution context)

- Files: `apps/server/migrations/tenant/` (namespace `App\Migrations\Tenant`)
- Version table: `tenant_migration_versions`, created **inside each tenant
  database**.

Tenant connections are built at runtime from each tenant's registry location
metadata. Every command **fails closed**: if a tenant is unknown, or its context
or database cannot be established, that tenant is not migrated and the run never
falls back to the central database or another tenant. Tenant context is established
and disposed per tenant, including on failure.

```bash
# One tenant (single-tenant context), by slug — exits non-zero if unknown/unreachable:
docker compose exec app php bin/console tenant:migrations:migrate <tenant-slug>

# All registered tenants (cross-tenant context); continues past failures,
# exits non-zero if any tenant failed:
docker compose exec app php bin/console tenant:migrations:migrate-all

# Only newly provisioned tenants (no applied tenant migrations yet):
docker compose exec app php bin/console tenant:migrations:migrate-all --new-only

# Per-tenant status (cross-tenant context): current version + pending count:
docker compose exec app php bin/console tenant:migrations:status
```

Provisioning runs the new-tenant migration path against a freshly provisioned
tenant database **before** the tenant is activated
(`TenantMigrationOrchestrator::migrateProvisionedTenant()`).

## Authoring a tenant migration

Place the migration in `apps/server/migrations/tenant/` under the
`App\Migrations\Tenant` namespace. Tenant migrations may use PostgreSQL-specific
features (ADR-005).
