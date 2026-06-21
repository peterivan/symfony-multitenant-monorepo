## Why

The tenancy layer (ADR-001) requires a central database holding the Tenant Registry plus a Doctrine setup that keeps central and tenant database access distinguishable, but Doctrine is not yet installed and the existing `central` PostgreSQL database has no schema, ORM mapping, or migrations pipeline. This foundation must exist before any tenant-DB connectivity, identity, or auth work can build on it.

## What Changes

- Install Doctrine ORM, DBAL, and Doctrine Migrations into `apps/server` via Composer.
- Configure a single, explicitly-named `central` Doctrine connection and EntityManager wired to the existing `DATABASE_URL` (`...@database:5432/central`), structured so future tenant connections/EntityManagers are clearly separable in configuration and code.
- Define a `Tenant` registry entity in the central database holding the globally-unique, immutable tenant identifier, the tenant slug, the tenant lifecycle state, and database-location metadata sufficient to open the tenant's PostgreSQL database.
- Establish a central-only migrations pipeline (dedicated configuration, namespace, and migrations directory) and generate the initial migration creating the tenant registry table.
- The central database stores registry, routing, and provisioning state ONLY — never tenant-owned data.

## Capabilities

### New Capabilities
- `central-database`: Doctrine installation and an explicitly-named central connection, EntityManager, and central-only migrations pipeline, separated from future tenant access. (→ specs/central-database/spec.md)
- `tenant-registry`: A central-owned Tenant registry entity carrying tenant identity, slug, lifecycle state, and tenant-DB location metadata sufficient to open the tenant database. (→ specs/tenant-registry/spec.md)

### Modified Capabilities

## Impact

- Dependencies: adds `doctrine/orm`, `doctrine/doctrine-bundle`, `doctrine/doctrine-migrations-bundle` (and transitively `doctrine/dbal`, `doctrine/migrations`) to `apps/server/composer.json`.
- Config: new `apps/server/config/packages/doctrine.yaml` and `doctrine_migrations.yaml`; consumes existing `DATABASE_URL` env var (no compose change required).
- Code: new central entity under `apps/server/src/` (e.g. `App\Central\Entity\Tenant`) and a central migrations directory (e.g. `apps/server/migrations/central/`).
- Systems: introduces the central persistence layer the tenancy boundary (ADR-001) depends on; no Redis/broker/other infra (ADR-005, PostgreSQL-only).
- Out of scope: tenant connections/EntityManagers, tenant migrations, identity/auth, provisioning execution.
