# tenant-database-routing Specification

## Purpose
TBD - created by archiving change tenant-database-connectivity. Update Purpose after archive.
## Requirements
### Requirement: Tenant database selection from registry metadata

The system SHALL select and open the correct tenant database connection and EntityManager from the tenant registry database-location metadata provided by `central-database-foundation`, keyed by the tenant identifier in the active tenant context.

#### Scenario: Open tenant database for resolved tenant

- **WHEN** an execution unit with an active, resolved tenant context requests a tenant connection or tenant EntityManager
- **THEN** the system looks up that tenant in the registry, reads its database-location metadata, and opens a tenant connection and EntityManager bound to that tenant's database

#### Scenario: Location metadata is more than a database name

- **WHEN** the system opens a tenant database
- **THEN** it uses the full database-location metadata required to connect, not only the database name

### Requirement: Selection before tenant EntityManager or connection use

The system SHALL complete tenant database selection before any tenant EntityManager or connection is used within an execution unit, and SHALL expose tenant Doctrine access only through the tenancy routing layer.

#### Scenario: Selection precedes first tenant query

- **WHEN** tenant-scoped code obtains a tenant EntityManager or connection through the tenancy routing layer
- **THEN** the tenant database has already been selected and opened for the active tenant before that EntityManager or connection performs any operation

#### Scenario: No tenant Doctrine access outside the routing layer

- **WHEN** code attempts to obtain a tenant connection or tenant EntityManager without going through the tenancy routing layer
- **THEN** no tenant-bound connection or EntityManager is provided

### Requirement: Fail closed on missing or unresolved tenant context

The system SHALL fail closed when tenant context is missing, unresolved, ambiguous, or inactive, and SHALL NOT fall back to the central database, a default tenant, or stale tenant context.

#### Scenario: No active tenant context

- **WHEN** a tenant connection or tenant EntityManager is requested while no active tenant context is present
- **THEN** the system raises an error and provides no tenant connection or EntityManager

#### Scenario: Unresolved or ambiguous tenant

- **WHEN** the active context did not resolve to exactly one stable tenant identifier
- **THEN** the system fails closed and does not open any tenant database

#### Scenario: Inactive tenant

- **WHEN** the resolved tenant's lifecycle state does not permit tenant-facing access
- **THEN** the system fails closed and does not open the tenant database

#### Scenario: No fallback to central or default

- **WHEN** tenant context cannot be proven for a tenant connection request
- **THEN** the system does not return the central connection, a default tenant connection, or a connection from stale context

### Requirement: Fail closed on registry lookup failure

The system SHALL fail closed when the registry lookup fails, the tenant is not found, or the database-location metadata is incomplete.

#### Scenario: Tenant not found in registry

- **WHEN** the resolved tenant identifier has no matching entry in the tenant registry
- **THEN** the system raises an error and opens no tenant database

#### Scenario: Registry lookup error

- **WHEN** the registry lookup fails (for example the central database is unreachable)
- **THEN** the system fails closed and does not open any tenant database

#### Scenario: Incomplete location metadata

- **WHEN** the registry entry lacks the database-location metadata required to connect
- **THEN** the system fails closed and does not attempt to guess a connection target

### Requirement: Central and tenant Doctrine access distinguishable

The system SHALL keep central and tenant Doctrine access distinguishable in configuration and in code, using named connections and entity managers and separate mapping boundaries.

#### Scenario: Distinct named connections and managers

- **WHEN** Doctrine configuration is inspected
- **THEN** the central connection/EntityManager and the tenant connection/EntityManager are separately named and have separate mapping boundaries

#### Scenario: Code selects the tenant manager explicitly

- **WHEN** tenant-scoped code needs tenant data access
- **THEN** it obtains the tenant EntityManager explicitly through the tenancy routing layer rather than an ambiguous default manager

### Requirement: A tenant database belongs to exactly one tenant

The system SHALL map exactly one resolved tenant identifier to one tenant database and SHALL NOT multiplex a single tenant connection or EntityManager across more than one tenant.

#### Scenario: One tenant maps to one database

- **WHEN** the routing layer resolves a tenant connection for a tenant identifier
- **THEN** that connection and EntityManager are bound to that tenant's single database only

#### Scenario: Different tenant requires re-resolution

- **WHEN** the active tenant identifier differs from the tenant a previously opened connection was bound to
- **THEN** the system re-resolves and opens the connection for the current tenant rather than reusing the prior tenant's connection

### Requirement: Release tenant Doctrine resources at end of execution unit

The system SHALL release tenant Doctrine connections and entity managers when the execution unit ends, including on failure paths, and SHALL NOT leak tenant context or connections across execution units.

#### Scenario: Resource cleanup after a request

- **WHEN** an HTTP request that opened a tenant connection ends
- **THEN** the tenant connection and EntityManager are released for that execution unit

#### Scenario: Resource cleanup on failure

- **WHEN** an execution unit that opened a tenant connection ends with an exception
- **THEN** the tenant connection and EntityManager are still released

#### Scenario: Per-iteration cleanup in cross-tenant execution

- **WHEN** a cross-tenant execution iterates from one tenant to the next
- **THEN** the prior tenant's Doctrine resources and context are disposed before the next tenant's database is opened

### Requirement: Tenant-namespacing of shared infrastructure holding tenant data

The system SHALL ensure that shared caches, sessions, and storage holding tenant-scoped data are tenant-namespaced or routed per tenant so tenant data does not leak across tenants.

#### Scenario: Shared cache is tenant-namespaced

- **WHEN** a shared cache stores tenant-scoped data under the routing layer
- **THEN** entries are namespaced by the active tenant identifier so one tenant cannot read another tenant's cached data

#### Scenario: Shared session or storage carrying tenant data

- **WHEN** a shared session or storage component holds tenant-scoped data
- **THEN** it is tenant-namespaced or routed per tenant rather than shared without isolation

