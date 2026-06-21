## 1. Tenant Migration Configuration

- [x] 1.1 Add the Doctrine Migrations bundle to `apps/server` if not already present via the central migration setup, confirming with `docker compose exec app composer show | grep doctrine-migrations`.
- [x] 1.2 Define a separate `tenant` migration configuration with its own migrations directory (`migrations/tenant`) and namespace, distinct from the central migration directory and namespace.
- [x] 1.3 Configure the tenant migration version-tracking table so it is created inside each tenant database, separate from the central migration version table.
- [x] 1.4 Wire the tenant migration configuration to use the tenant connection/EntityManager from `tenant-database-connectivity` rather than the central connection.

## 2. Orchestration Service

- [x] 2.1 Implement a tenant-migration orchestration service that enumerates target tenants from the Tenant registry (`central-database-foundation`).
- [x] 2.2 Implement single-tenant resolution by tenant identifier, failing closed when the tenant is unknown.
- [x] 2.3 Implement all-tenants enumeration over every registered tenant.
- [x] 2.4 Implement new-tenants-only selection by detecting tenants whose tenant migration version state is empty.
- [x] 2.5 For each target, establish active tenant context via the tenancy layer, run tenant migrations, record per-tenant migration state, and dispose tenant context, including on the failure path.
- [x] 2.6 Implement fail-closed handling: refuse to migrate any target whose tenant context or tenant database connection cannot be established, never falling back to the central database or a default tenant.
- [x] 2.7 Track per-tenant tenant-migration state (current version, pending, failed) for status reporting and resumable cross-tenant runs.

## 3. CLI Commands With Declared Execution Context

- [x] 3.1 Add a single-tenant migration command that declares single-tenant execution context and accepts a tenant identifier, wrapping `docker compose exec app php bin/console doctrine:migrations:migrate --configuration=<tenant config>` for the resolved tenant connection.
- [x] 3.2 Add a cross-tenant migration command that declares cross-tenant execution context and supports all-tenants and new-tenants-only modes.
- [x] 3.3 Add a tenant migration status command (cross-tenant context) reporting each tenant's current version and pending tenant migrations via `docker compose exec app php bin/console doctrine:migrations:status --configuration=<tenant config>` per tenant.
- [x] 3.4 Ensure each migration-related command declares its execution context (central / single-tenant / cross-tenant) and reject any command lacking a declared context.
- [x] 3.5 Confirm the central migration command path remains declared as central context and runs only `docker compose exec app php bin/console doctrine:migrations:migrate` against the central database.

## 4. Provisioning Integration

- [x] 4.1 Invoke the new-tenant tenant-migration path during tenant provisioning so tenant migrations run against the new tenant database before the tenant is activated.

## 5. Authoring And Verification

- [x] 5.1 Generate an initial tenant migration with `docker compose exec app php bin/console doctrine:migrations:generate --configuration=<tenant config>` to confirm files land in the tenant directory/namespace.
- [x] 5.2 Add tests covering fail-closed behavior (unknown tenant, unreachable tenant database), per-iteration context disposal, single/all/new run modes, and central-vs-tenant migration separation.
- [x] 5.3 Run the static analysis and test suite via `docker compose exec app` (PHPStan, PHPUnit) and fix issues.
- [x] 5.4 Document operator usage for single-tenant, all-tenants, and new-tenants migration runs using the `docker compose exec app` commands.
