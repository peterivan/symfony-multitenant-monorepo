# tenant-migrations Specification

## Purpose
TBD - created by archiving change tenant-migrations. Update Purpose after archive.
## Requirements
### Requirement: Tenant Migrations Separate From Central Migrations

The system SHALL maintain a tenant migration pipeline that is separate from the central migration pipeline, with its own migration files, its own migration namespace, and its own migration version-tracking table stored inside each tenant database.

#### Scenario: Tenant and central migration files are isolated

- **WHEN** a developer adds a tenant migration
- **THEN** the migration file is placed in the tenant migration directory and namespace, separate from the central migration directory and namespace, and is never executed by the central migration pipeline

#### Scenario: Tenant migration version tracking lives in the tenant database

- **WHEN** a tenant migration is applied to a tenant database
- **THEN** the applied version is recorded in a tenant-side migration version table inside that tenant's database, distinct from the central migration version table in the central database

#### Scenario: Tenant migrations may use PostgreSQL-specific features

- **WHEN** a tenant migration defines schema changes
- **THEN** the migration MAY use PostgreSQL-specific DDL or features, because tenant databases are PostgreSQL-only

### Requirement: Run Tenant Migrations For A Single Tenant

The system SHALL run tenant migrations for one specified tenant identified by its tenant identifier, applying only that tenant's migrations against that tenant's database.

#### Scenario: Single-tenant migration applies to the named tenant only

- **WHEN** an operator runs the tenant migration command for a single tenant identifier
- **THEN** the system establishes that tenant's context, applies pending tenant migrations to that tenant's database only, records the new tenant migration state, and disposes the tenant context

#### Scenario: Unknown single tenant fails closed

- **WHEN** an operator runs the single-tenant migration command for a tenant identifier that is not in the tenant registry
- **THEN** the system does not apply any migration, does not fall back to the central database or a default tenant, and exits with a non-zero status

### Requirement: Run Tenant Migrations Across All Tenants

The system SHALL run tenant migrations across all registered tenants, iterating tenant by tenant and applying pending tenant migrations to each tenant's database.

#### Scenario: Cross-tenant migration iterates every registered tenant

- **WHEN** an operator runs the cross-tenant migration command for all tenants
- **THEN** the system enumerates registered tenants from the tenant registry, and for each tenant establishes tenant context, applies pending tenant migrations, records state, and disposes tenant context before moving to the next tenant

#### Scenario: Cross-tenant run continues past a failed tenant and reports failures

- **WHEN** a tenant in a cross-tenant run cannot have its context or database connection established, or its migration fails
- **THEN** the system records that tenant as failed, continues with the remaining tenants without falling back to the central database or a default tenant, and exits with a non-zero status if any tenant failed

### Requirement: Run Tenant Migrations For Newly Provisioned Tenants Only

The system SHALL run tenant migrations for only newly provisioned tenants, defined as tenants whose tenant database has no applied tenant migrations.

#### Scenario: New-tenant migration targets only never-migrated tenants

- **WHEN** an operator runs the tenant migration command in new-tenants-only mode
- **THEN** the system selects only tenants whose tenant migration version state is empty, applies tenant migrations to those tenants, and skips tenants that already have applied tenant migrations

#### Scenario: Provisioning runs tenant migrations before activation

- **WHEN** a tenant is provisioned and its tenant database is created or registered
- **THEN** the system runs tenant migrations against the new tenant database before the tenant is activated

### Requirement: Track Tenant Migration State

The system SHALL track per-tenant tenant-migration state so orchestration can determine which tenants are pending, current, or failed.

#### Scenario: Status reporting shows per-tenant migration state

- **WHEN** an operator requests tenant migration status across tenants
- **THEN** the system reports, for each tenant, its current applied tenant migration version and whether pending tenant migrations exist

### Requirement: CLI Commands Declare Execution Context

The system SHALL require every migration-related CLI command to declare whether it operates in central context, single-tenant context, or cross-tenant context.

#### Scenario: Central migration command declares central context

- **WHEN** a CLI command runs central migrations
- **THEN** the command declares central execution context and operates only against the central database

#### Scenario: Tenant migration command declares single-tenant or cross-tenant context

- **WHEN** a CLI command runs tenant migrations
- **THEN** the command declares either single-tenant context (for one tenant identifier) or cross-tenant context (for all tenants or new tenants only)

#### Scenario: Command without a declared execution context is rejected

- **WHEN** a migration-related CLI command does not declare an execution context
- **THEN** the system rejects the command and does not perform any migration

### Requirement: Fail Closed When Tenant Context Or Database Cannot Be Established

The system SHALL fail closed when a target tenant's tenant context or tenant database connection cannot be established, and SHALL NOT fall back to the central database, a default tenant, or stale tenant context.

#### Scenario: Tenant database connection cannot be opened

- **WHEN** the system attempts to migrate a target tenant whose tenant database connection cannot be established
- **THEN** the system does not apply any migration to that target, does not fall back to the central database or any other tenant, records the target as failed, and surfaces a non-zero result

#### Scenario: Tenant context is disposed after each tenant including on failure

- **WHEN** the system finishes a tenant iteration in a cross-tenant run, whether it succeeded or failed
- **THEN** the system disposes the active tenant context and tenant Doctrine resources before the next iteration so no tenant context leaks across iterations

