# tenant-registry Specification

## Purpose
TBD - created by archiving change central-database-foundation. Update Purpose after archive.
## Requirements
### Requirement: Tenant Registry Entity in Central Database

The system SHALL define a central-owned Tenant registry entity, mapped to the central EntityManager and stored in the central database, representing each tenant known to the platform.

#### Scenario: Tenant entity mapped to central manager

- **WHEN** the Tenant registry entity mapping is inspected
- **THEN** the Tenant entity is mapped to the explicitly named `central` EntityManager and persisted in the central database

#### Scenario: Registry table created by central migration

- **WHEN** the central migrations pipeline has been applied
- **THEN** a table backing the Tenant registry entity exists in the central database

### Requirement: Globally Unique Immutable Tenant Identifier

The Tenant registry entity SHALL carry a tenant identifier that is globally unique and immutable after creation, and SHALL carry the tenant slug used for resolution.

#### Scenario: Tenant identifier is unique and stable

- **WHEN** the Tenant entity definition and its migration are inspected
- **THEN** the tenant identifier is stored as a PostgreSQL `uuid` and enforced unique as the primary key
- **AND** the identifier has no setter or update path after creation

#### Scenario: Tenant slug is unique

- **WHEN** the Tenant entity definition and its migration are inspected
- **THEN** the tenant slug column carries a unique constraint
- **AND** two tenants cannot be persisted with the same slug

### Requirement: Tenant Database Location Metadata Sufficient to Open the Tenant Database

The Tenant registry entity SHALL store database-location metadata sufficient to open the tenant's PostgreSQL database, including more than just the database name, so a tenant connection can be constructed across the shared, dedicated-DB, and dedicated-deployment isolation tiers.

#### Scenario: Location metadata exceeds database name

- **WHEN** the Tenant entity definition is inspected
- **THEN** it stores the tenant database host, port, and database name as discrete fields
- **AND** it stores additional connection parameters in a PostgreSQL `jsonb` field for tier-specific or dedicated-instance options

#### Scenario: Credentials are not stored as plaintext

- **WHEN** the Tenant entity definition is inspected
- **THEN** tenant database credentials are referenced indirectly and no plaintext password is stored in the registry row

#### Scenario: Metadata enables a tenant connection

- **WHEN** a Tenant registry row is read
- **THEN** the stored location metadata is sufficient to construct a Doctrine DBAL connection to that tenant's database without relying on hardcoded global tenant database settings

### Requirement: Centrally Managed Tenant Lifecycle State

The Tenant registry entity SHALL store a centrally managed tenant lifecycle state that can represent at least the `active`, `suspended`, `archived`, and `deleted` states.

#### Scenario: Lifecycle state column exists with required states

- **WHEN** the Tenant entity definition and its migration are inspected
- **THEN** a lifecycle state field exists that is constrained to a value set including at least `active`, `suspended`, `archived`, and `deleted`

### Requirement: Registry Holds Only Central Routing and Provisioning Metadata

The Tenant registry entity SHALL hold only tenant registry, routing, and provisioning metadata and SHALL NOT hold tenant-owned business data.

#### Scenario: Registry fields are central-owned metadata only

- **WHEN** the Tenant entity fields are reviewed
- **THEN** every field is registry, routing (slug/location), lifecycle, or provisioning metadata
- **AND** no field stores tenant-owned business data

