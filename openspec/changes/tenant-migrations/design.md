## Context

ADR-001 mandates database-per-tenant with separate Doctrine connections and mapping boundaries for central and tenant data, and states explicitly that tenant migrations are separate from central migrations and must be runnable for one tenant, all tenants, and newly provisioned tenants, with orchestration tracking tenant migration state. ADR-001 also requires that CLI commands, workers, and background jobs declare whether they run in central context, a single tenant context, or across tenants, and that tenant-scoped access fails closed when tenant context cannot be established. ADR-005 establishes PostgreSQL 16 as the only database engine, so tenant migrations may use PostgreSQL-specific features.

This change builds on two existing changes:

- `central-database-foundation`: provides Doctrine wiring, the central connection/EntityManager, and the Tenant registry that lists tenants and their database location metadata.
- `tenant-database-connectivity`: provides runtime selection of a tenant database via the active tenant context (the tenancy layer owns context creation/replacement/clearing).

Doctrine Migrations (doctrine/doctrine-migrations-bundle) supports multiple named migration configurations, each with its own migrations directory, namespace, EntityManager/connection, and version-tracking table (`metadata_storage`). This is the mechanism used to keep tenant migrations physically and logically separate from central migrations.

## Goals / Non-Goals

**Goals:**

- Provide a tenant migration pipeline whose migration files, namespace, and version table are separate from the central migration pipeline.
- Run tenant migrations for a single named tenant, for all registered tenants, or for only newly provisioned tenants (those with no applied tenant migrations).
- Track per-tenant tenant-migration state (which tenants are pending, current, or failed) for orchestration and status reporting.
- Make every migration-related CLI command declare its execution context: central, single-tenant, or cross-tenant.
- Establish and dispose active tenant context per tenant iteration, including on failure, and never fall back to the central database, a default tenant, or stale context.
- Fail closed when a target tenant's context or tenant database connection cannot be established.

**Non-Goals:**

- Defining the central migration pipeline (owned by `central-database-foundation`).
- Defining tenant resolution, tenant context lifecycle internals, or runtime connection selection (owned by the tenancy layer / `tenant-database-connectivity`).
- Implementing the full tenant provisioning workflow (this change only specifies that provisioning invokes tenant migrations before activation; the workflow lives with provisioning).
- Cross-database atomic transactions, multi-region placement, sharding, or zero-downtime online schema-change tooling.
- Data backfill strategy and large-table rollout sequencing beyond the migration mechanism itself.

## Decisions

- **Separate Doctrine Migrations configuration for tenants.** Define a `tenant` migration configuration with its own migrations directory (e.g. `migrations/tenant`), its own namespace, and its own version-tracking table inside each tenant database, distinct from the central configuration's directory, namespace, and version table. Central and tenant migration directories never mix.
- **Orchestration over the Tenant registry.** A tenant-migration orchestration service reads the Tenant registry (central database) to enumerate target tenants, then iterates: establish tenant context, run `doctrine:migrations` against that tenant's connection, record state, dispose context.
- **Three run modes.** Single tenant (by tenant identifier), all tenants (every registered tenant), and new tenants only (tenants whose tenant version table is empty / absent, i.e. never migrated). New-tenant mode is what provisioning and routine onboarding use.
- **Execution-context declaration is mandatory.** Each command declares its context explicitly (central / single-tenant / cross-tenant) via a command attribute/configuration. Tenant-migration commands declare single-tenant or cross-tenant; a separate central command path declares central. This keeps ADR-001's context-declaration invariant verifiable.
- **Fail closed per target.** Before applying migrations to a tenant, the orchestrator requires a successfully established tenant context and a usable tenant database connection. If either cannot be established for a target, that target is failed (recorded), and the run never silently uses the central database or a default tenant. Single-tenant runs exit non-zero; cross-tenant runs continue to remaining tenants and report failures, exiting non-zero if any failed.
- **Context disposal per iteration.** Active tenant context and tenant Doctrine resources are established and disposed per tenant iteration, including on the failure path, so no context leaks across iterations in a long-running cross-tenant run.
- **PostgreSQL-specific schema allowed.** Tenant migration files may use PostgreSQL-specific DDL/features per ADR-005; portability is not a goal.
- **All tooling via Docker.** Commands run as `docker compose exec app php bin/console ...`, wrapping `doctrine:migrations:*` with the `--em`/configuration selecting the tenant migration configuration.

## Risks / Trade-offs

- **Partial cross-tenant runs.** A cross-tenant migration can leave some tenants migrated and others not (no atomicity across databases, per ADR-001). Mitigation: track per-tenant state, make runs resumable/idempotent, report failed tenants, and exit non-zero so failures are visible.
- **Drift between tenant databases.** Different tenants can sit at different migration versions after partial runs. Mitigation: a status command reporting each tenant's current version vs. latest available, plus new-tenant detection by empty version table.
- **Registry/connection availability.** Orchestration depends on the central registry and per-tenant connectivity; an unreachable tenant DB fails that target. Mitigation: fail closed per target, continue cross-tenant, never fall back centrally.
- **Long-running cross-tenant runs.** Many tenants make full runs slow and increase context-leak risk. Mitigation: strict per-iteration context establish/dispose and per-tenant state tracking to support resumption.
- **Two migration pipelines to maintain.** Separate central and tenant configurations add operational surface. Trade-off accepted because ADR-001 requires the separation; the alternative (shared pipeline) would violate central-vs-tenant boundaries.
