# execution-context-declaration Specification

## Purpose
TBD - created by archiving change tenant-boundary-enforcement. Update Purpose after archive.
## Requirements
### Requirement: Execution Context Declaration For Commands, Workers, And Jobs
The system SHALL require every CLI command, worker, and background job to declare whether it runs in central, single-tenant, or cross-tenant execution context through an explicit attribute, interface, or configuration.

#### Scenario: Command declares central context
- **WHEN** a CLI command operates only on central-owned data
- **THEN** it declares central execution context and runs without an active tenant context

#### Scenario: Command declares single-tenant context
- **WHEN** a CLI command operates on one tenant's data
- **THEN** it declares single-tenant execution context and requires an explicitly selected tenant before tenant-scoped access

#### Scenario: Job declares cross-tenant context
- **WHEN** a background job iterates across tenants
- **THEN** it declares cross-tenant execution context and establishes tenant context per tenant iteration

### Requirement: Fail Closed On Undeclared Execution Context
The system SHALL fail closed for any CLI command, worker, or background job that does not declare its execution context, rather than defaulting to any tenant.

#### Scenario: Undeclared execution unit is rejected
- **WHEN** a command, worker, or job runs without declaring central, single-tenant, or cross-tenant context
- **THEN** it fails closed and does not perform tenant-scoped access under an implicit or default tenant

### Requirement: No Implicit Tenant Context Inheritance Across Async Boundaries
The system SHALL strip active tenant context by default across async boundaries and SHALL NOT implicitly inherit tenant context into Messenger messages, queues, or scheduled jobs.

#### Scenario: Dispatch strips active tenant context
- **WHEN** a message is dispatched from within an active tenant context
- **THEN** the active tenant context is not implicitly carried into the dispatched message's execution unit

#### Scenario: Consumer starts without inherited tenant context
- **WHEN** a worker consumes a message
- **THEN** it starts with no active tenant context and does not reuse the dispatcher's tenant context

### Requirement: Re-Establish Tenant Context Only From Explicit Routing Metadata
The system SHALL re-establish tenant context for an async execution unit only from explicit routing metadata, job attributes, or command options.

#### Scenario: Tenant re-established from explicit metadata
- **WHEN** a tenant-scoped message carries explicit tenant routing metadata and is consumed
- **THEN** tenant context is established only from that explicit metadata before tenant-scoped access

#### Scenario: Tenant-scoped message without resolvable metadata fails closed
- **WHEN** a tenant-scoped message is consumed but carries no resolvable explicit tenant metadata
- **THEN** the execution unit fails closed and performs no tenant-scoped access

### Requirement: Tenant Context Established And Disposed Per Execution Unit
The system SHALL establish tenant context per execution unit and clear it when the unit ends, including failure paths, and tenant-scoped execution SHALL NOT fall back to central storage, a default tenant, or stale tenant context.

#### Scenario: Cross-tenant iteration disposes context per tenant
- **WHEN** a cross-tenant job processes multiple tenants
- **THEN** tenant context is established and disposed for each tenant iteration, including iterations that fail

#### Scenario: Context cleared after failure
- **WHEN** a single-tenant execution unit fails mid-execution
- **THEN** the active tenant context is cleared so it does not leak into the next execution unit

#### Scenario: No fallback to default or stale tenant
- **WHEN** a tenant-scoped execution unit has no validly established tenant context
- **THEN** it fails closed instead of falling back to central storage, a default tenant, or a previously active tenant context

### Requirement: No Cross-Tenant Behavior Via Implicit Shared State Across Execution Units
The system SHALL NOT introduce cross-tenant behavior through implicit shared state carried across execution units or async boundaries.

#### Scenario: Shared state does not carry tenant scope between units
- **WHEN** successive execution units run in different tenant contexts
- **THEN** no tenant context, tenant-local state, or tenant-owned data is carried from one execution unit to another via implicit shared or static state

