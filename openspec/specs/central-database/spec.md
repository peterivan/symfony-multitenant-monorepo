# central-database Specification

## Purpose
TBD - created by archiving change central-database-foundation. Update Purpose after archive.
## Requirements
### Requirement: Doctrine ORM and Migrations Installed

The system SHALL install Doctrine ORM, DBAL, and Doctrine Migrations in `apps/server` so the central persistence layer and its migrations pipeline are available.

#### Scenario: Doctrine dependencies present

- **WHEN** `apps/server/composer.json` and the installed vendor tree are inspected
- **THEN** `doctrine/orm`, `doctrine/doctrine-bundle`, and `doctrine/doctrine-migrations-bundle` are present and installed

#### Scenario: Doctrine console available

- **WHEN** `docker compose exec app php bin/console list doctrine` is run
- **THEN** Doctrine ORM and migrations commands are listed without configuration errors

### Requirement: Explicitly Named Central Connection and EntityManager

The system SHALL provide a Doctrine connection and EntityManager explicitly identified as `central`, wired to the existing `DATABASE_URL` central database, and distinguishable in configuration and code from future tenant connections and EntityManagers.

#### Scenario: Central connection configured by name

- **WHEN** the Doctrine configuration in `apps/server/config/packages/` is inspected
- **THEN** a connection explicitly named `central` is defined and bound to the `central` PostgreSQL database referenced by `DATABASE_URL`
- **AND** a corresponding EntityManager explicitly named `central` is defined

#### Scenario: Central manager maps only central entities

- **WHEN** the central EntityManager mapping configuration is inspected
- **THEN** it maps only the dedicated central entity namespace and directory and does not map any tenant entity namespace

#### Scenario: Central access path is distinguishable from tenant access

- **WHEN** application code obtains the central EntityManager
- **THEN** it resolves the manager through the explicit `central` identifier rather than an unnamed default that future tenant access could share

### Requirement: Central Stores Only Central-Owned Data

The system SHALL restrict the central database to tenant registry, routing, and provisioning metadata and SHALL NOT store tenant-owned business data in the central database.

#### Scenario: Only central-owned entities mapped to central

- **WHEN** the entities mapped to the central EntityManager are reviewed
- **THEN** every mapped entity represents central-owned registry, routing, or provisioning metadata
- **AND** no mapped entity represents tenant-owned business data

### Requirement: Central-Only Migrations Pipeline

The system SHALL establish a migrations pipeline dedicated to central migrations, with a central-specific migrations namespace and directory, kept separate from any future tenant migrations pipeline.

#### Scenario: Central migrations configured separately

- **WHEN** `apps/server/config/packages/doctrine_migrations.yaml` is inspected
- **THEN** a central-specific migrations namespace and directory are configured and are not a shared default that tenant migrations would reuse

#### Scenario: Central migration creates the registry table

- **WHEN** `docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction` is run against the central database
- **THEN** the migration runs against the central connection and creates the tenant registry table

#### Scenario: Central migration status is tracked

- **WHEN** `docker compose exec app php bin/console doctrine:migrations:status` is run
- **THEN** the central migration is reported as executed and tracked in the central database

