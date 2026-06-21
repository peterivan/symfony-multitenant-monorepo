## Context

ADR-001 mandates a custom tenancy layer with database-per-tenant isolation: one central PostgreSQL database for the Tenant Registry, routing, and provisioning metadata, and one PostgreSQL database per tenant for tenant-owned data, with separate Doctrine connections, EntityManagers, and mapping boundaries for central versus tenant data. ADR-005 makes PostgreSQL 16 the only datastore and permits PostgreSQL-specific features freely.

Today the `central` database exists and is reachable through `DATABASE_URL`, and tenant resolution components (`TenantContext`, `TenantResolver`, `TenantSubscriber`) already resolve a host to a tenant slug. However Doctrine is not installed, so there is no ORM, no persisted Tenant Registry, and no migrations pipeline. This change delivers only the central foundation; tenant connectivity and migrations follow later.

## Goals / Non-Goals

**Goals:**
- Install Doctrine ORM/DBAL/Migrations in `apps/server`.
- Provide one explicitly-named `central` connection and EntityManager, with configuration and code shaped so future tenant connections/EntityManagers are unambiguously separable.
- Persist a central-owned Tenant Registry entity carrying tenant identity, slug, lifecycle state, and tenant-DB location metadata sufficient to open the tenant database.
- Establish a central-only migrations pipeline and create the registry table via migration.

**Non-Goals:**
- Defining or opening tenant connections/EntityManagers, or tenant migrations.
- Tenant identity, authentication, authorization (these are tenant-local per ADR-001).
- Provisioning execution, lifecycle transition workflows, or registry caching/invalidation.
- Tenant resolution changes (existing resolver/context untouched here).
- Any non-PostgreSQL infrastructure (ADR-005).

## Decisions

- **Single named connection now, multi-manager-ready structure.** Configure Doctrine with a `central` connection and a `central` EntityManager rather than relying on the implicit `default` name, so that adding tenant connections later is purely additive and central code never accidentally uses a tenant manager. The central EntityManager maps only the central entity namespace/directory. Alternative considered: use the default unnamed connection now and rename later — rejected because renaming a live default connection is a breaking migration and weakens the central-vs-tenant distinction ADR-001 requires from the start.
- **Dedicated central namespace for entities.** Central entities live under a `App\Central\...` namespace and directory, mapped exclusively to the central EntityManager. This makes the central-vs-tenant code boundary visible (ADR-001) and prevents future tenant entities from being mapped into the central manager.
- **Central-only migrations pipeline.** Use `doctrine-migrations-bundle` configured with a single migrations namespace and directory dedicated to central migrations (e.g. `migrations/central`). Tenant migrations will get their own separate pipeline later (ADR-001 keeps tenant and central migrations separate); naming the central pipeline now avoids a default catch-all that future tenant migrations could leak into.
- **Tenant identifier model.** The registry stores a globally-unique, immutable tenant identifier (UUID, stored as PostgreSQL `uuid`) as the primary key, plus the human-facing `slug` (unique) used by the existing resolver. Immutability and global uniqueness are ADR-001 invariants; UUID satisfies global uniqueness across shared and dedicated deployments.
- **Tenant-DB location metadata sufficient to open the DB.** The registry stores enough connection information to construct a tenant DBAL connection — not just the database name. Stored as discrete columns where stable (host, port, database name) plus a JSONB column for additional connection parameters/options, allowing PostgreSQL-native flexibility (ADR-005) across the three isolation tiers (shared, dedicated DB, dedicated deployment) without schema churn. Credentials/secrets are referenced indirectly (e.g. a credentials reference), not stored as plaintext, to keep secret management out of the registry rows. Alternative considered: store only the database name and derive host/port from global config — rejected because ADR-001 explicitly requires metadata sufficient to open the DB beyond the name, which dedicated-instance tenants need.
- **Lifecycle state column.** Store a tenant lifecycle state with at least `active`, `suspended`, `archived`, `deleted` (ADR-001), as a constrained string/enum column. This change records state; it does not implement transition operations.
- **PostgreSQL-native types.** Use `uuid`, `jsonb`, unique constraints, and `timestamptz` audit columns (created/updated) per ADR-005; no portability shims.

## Risks / Trade-offs

- [Storing tenant DB connection metadata centrally risks leaking secrets] → Store only location/parameters in the registry; reference credentials indirectly via a secrets reference, never plaintext passwords in rows.
- [A misconfigured single `default` manager could let central code silently reach a tenant DB later] → Use explicit `central` connection/manager names and a dedicated central entity namespace now, so tenant access cannot fall back onto the central manager and vice-versa (ADR-001 fail-closed / no-fallback intent).
- [Discrete location columns may not fit every isolation tier] → Pair stable columns with a JSONB options column so dedicated-instance and on-prem tenants can carry extra connection parameters without schema migrations.
- [Migrations pipeline left as default could later mix tenant and central migrations] → Name and isolate the central migrations namespace/directory up front.
- [Doctrine abstractions could discourage PostgreSQL features] → ADR-005 explicitly permits PG-native types; the design uses `uuid`/`jsonb`/`timestamptz` directly.
