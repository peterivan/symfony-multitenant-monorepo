## Why

ADR-001 requires tenant migrations to be separate from central migrations and runnable for one tenant, all tenants, and newly provisioned tenants, with migration orchestration tracking tenant migration state. It also requires CLI commands to declare whether they run in central context, a single tenant context, or across tenants, and tenant-scoped access to fail closed when tenant context cannot be established.

The `central-database-foundation` change provides Doctrine, the central connection, and the Tenant registry. The `tenant-database-connectivity` change provides runtime tenant database selection. Neither defines how tenant schema evolves over time. Without a dedicated tenant migration pipeline, schema changes to tenant databases would have to reuse the central migration path, which would violate the central-vs-tenant separation invariant and would not scale to many tenant databases or to onboarding new tenants.

This change introduces a tenant migration pipeline that is independent from central migrations, orchestrated across the tenant registry, and explicit about execution context.

## What Changes

- Add a tenant migration pipeline with its own migration configuration, namespace, and version-tracking table stored inside each tenant database, fully separate from the central migration pipeline.
- Add orchestration to run tenant migrations for a single tenant, for all registered tenants, or for only newly provisioned tenants that have never had tenant migrations applied.
- Track per-tenant tenant-migration state so orchestration can determine which tenants are pending, up to date, or failed.
- Add CLI commands that explicitly declare their execution context (central context, single-tenant context, or cross-tenant) for running and inspecting tenant migrations, built on top of `doctrine:migrations` running inside the `app` container.
- Establish active tenant context per tenant before applying that tenant's migrations and dispose it after each tenant, including on failure paths.
- Fail closed: refuse to apply tenant migrations to any target whose tenant context or tenant database connection cannot be established, and continue cross-tenant runs without silently falling back to the central database or a default tenant.
- Allow tenant migrations to use PostgreSQL-specific schema features (ADR-005) since tenant databases are PostgreSQL-only.

## Capabilities

### New Capabilities

- `tenant-migrations`: A migration pipeline for tenant databases that is separate from central migrations, runnable per single tenant, across all tenants, or for newly provisioned tenants only, with explicit execution-context declaration on CLI commands and fail-closed behavior when a tenant context or database cannot be established.

### Modified Capabilities

## Impact

- Adds a tenant-scoped Doctrine Migrations configuration (separate migration directory/namespace and a tenant-side migration version table) distinct from the central migration configuration.
- Adds console commands and an orchestration service that depend on the Tenant registry (`central-database-foundation`) and runtime tenant database selection (`tenant-database-connectivity`).
- Integrates with tenant provisioning: provisioning runs tenant migrations as part of initializing a new tenant database before the tenant is activated (ADR-001).
- No new infrastructure: PostgreSQL 16 only (ADR-005). All tooling runs via `docker compose exec app`.
- Operational change: running tenant migrations becomes a distinct, context-declared workflow separate from `doctrine:migrations:migrate` against the central database.
