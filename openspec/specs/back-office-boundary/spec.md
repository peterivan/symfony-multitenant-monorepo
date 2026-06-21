# back-office-boundary Specification

## Purpose
TBD - created by archiving change back-office-surface. Update Purpose after archive.
## Requirements
### Requirement: Back Office Runtime Surface Under /bo

The system SHALL expose the Back Office as a dedicated operational surface at runtime under the `/bo` path, reserved for platform-level operations and not for tenant business workflows.

#### Scenario: Back Office exposed under /bo

- **WHEN** route registration or inspection examines the runtime routes for the Back Office
- **THEN** the Back Office capabilities are exposed under the `/bo` path namespace

#### Scenario: Tenant business workflow rejected from Back Office

- **WHEN** a capability that implements a tenant-facing business workflow is proposed for the Back Office surface
- **THEN** the system SHALL reject it as outside the Back Office boundary

### Requirement: Central-Context-Only Routing

The system SHALL register Back Office routes as central-context-only routes that are not reachable as tenant-subdomain routes, tenant-facing routes, or tenant-scoped application flows.

#### Scenario: Back Office not reachable via tenant subdomain

- **WHEN** a request targets a Back Office route through a tenant-subdomain host
- **THEN** the system SHALL NOT serve the Back Office route as a tenant-subdomain route

#### Scenario: Route inspection confirms central-only registration

- **WHEN** route inspection lists where Back Office routes are registered
- **THEN** the routes appear only as central-context routes and are not registered as tenant-subdomain routes

### Requirement: Entry Without Active Tenant Context

The system SHALL start Back Office requests in central context without an active tenant context, and Back Office entry MUST NOT require tenant resolution or initialize tenant context from the request host.

#### Scenario: Back Office entry does not resolve a tenant

- **WHEN** a request enters a Back Office route
- **THEN** the system processes it in central context and does not require tenant resolution to enter

#### Scenario: Host does not initialize tenant context for Back Office

- **WHEN** a Back Office request arrives with a host that would otherwise resolve to a tenant
- **THEN** the system SHALL NOT initialize tenant context from the request host for that Back Office request

### Requirement: No Implicit Tenant Context Inheritance

The system SHALL NOT allow Back Office to implicitly inherit tenant context from tenant-facing requests, sessions, workers, or navigation state.

#### Scenario: Implicit tenant context not inherited

- **WHEN** a Back Office operation begins within an execution unit that previously carried tenant context from a tenant-facing request, session, or worker
- **THEN** the Back Office operation SHALL start without that inherited tenant context

### Requirement: Platform-Operator Authorization Boundary

The system SHALL authorize every Back Office action through platform-operator authorization only, and tenant-local users, memberships, roles, and permissions MUST NOT grant Back Office access.

#### Scenario: Platform operator authorized for Back Office

- **WHEN** a platform-operator identity with platform-operator authorization requests a Back Office action
- **THEN** the system evaluates access through the platform-operator authorization boundary

#### Scenario: Tenant-local user denied Back Office access

- **WHEN** a tenant-local user with tenant memberships, tenant roles, or tenant-local permissions requests a Back Office action
- **THEN** the system SHALL deny Back Office access because tenant-local identity does not grant Back Office access

#### Scenario: Tenant-local identity does not inherit platform-operator privileges

- **WHEN** a tenant-local identity attempts to perform a platform operation
- **THEN** the system SHALL NOT automatically grant platform-operator privileges to that tenant-local identity

### Requirement: Scoped Tenant-Context Lifecycle For Platform Operations

The system SHALL establish tenant context for a Back Office operation only from explicit platform-operator intent, scope it to that bounded operation, and clear it when the operation ends, including on failure. Tenant registry operations SHALL run in central context.

#### Scenario: Tenant context established only from explicit intent

- **WHEN** a Back Office operation against a tenant database initializes, migrates, seeds, inspects, or maintains a specific tenant selected by explicit platform-operator intent
- **THEN** the system establishes tenant context scoped to that bounded operation

#### Scenario: Tenant context cleared after the operation

- **WHEN** a Back Office operation that established tenant context completes
- **THEN** the system SHALL clear that tenant context when the operation ends

#### Scenario: Tenant context cleared on failure

- **WHEN** a Back Office operation that established tenant context fails before completing
- **THEN** the system SHALL clear that tenant context as part of ending the failed operation

#### Scenario: Tenant registry operation runs in central context

- **WHEN** a Back Office operation acts on the central tenant registry
- **THEN** the system runs it in central context without establishing tenant context

### Requirement: Explicit Tenant Scope For Tenant-Owned Data

The system SHALL require any Back Office capability that reads, derives, exports, mutates, or deletes tenant-owned data to make the selected tenant and data scope explicit, and SHALL fail closed when tenant scope is missing or ambiguous.

#### Scenario: Explicit tenant and scope for tenant-owned data

- **WHEN** a Back Office capability accesses or mutates tenant-owned data with an explicitly selected tenant and data scope
- **THEN** the system performs the operation within that explicit tenant scope

#### Scenario: Fail closed on missing or ambiguous tenant scope

- **WHEN** a Back Office capability attempts to access or mutate tenant-owned data without an explicit tenant selection or with an ambiguous tenant context
- **THEN** the system SHALL fail closed and SHALL NOT perform the operation

