## ADDED Requirements

### Requirement: Explicit Tenant-Facing Route Boundary
The system SHALL provide an explicit, inspectable boundary that classifies a route as tenant-facing, and tenant-facing routes MUST be distinguishable from Back Office and central platform routes through that classification.

#### Scenario: Tenant-facing route is explicitly marked
- **WHEN** a route is intended to be reached by tenant users inside tenant context
- **THEN** the route carries an explicit tenant-facing boundary marker that is inspectable via route configuration or controller metadata

#### Scenario: Route classification is unambiguous
- **WHEN** route classification is inspected
- **THEN** every route resolves to exactly one of tenant-facing, central platform, or Back Office, and no route is classified as both tenant-facing and Back Office / central platform

### Requirement: Tenant Context Required Before Tenant-Local Authn/Authz/Data
The system SHALL require a resolved, active, unambiguous tenant context on a tenant-facing route before any tenant-local authentication, authorization, or tenant-owned data access is evaluated.

#### Scenario: Enforcement precedes tenant-local authentication
- **WHEN** a request targets a tenant-facing route with a resolved, active tenant context
- **THEN** tenant context enforcement runs after tenant resolution and before tenant-local authentication and authorization, and the request proceeds to tenant-local handling

#### Scenario: Tenant-local authentication never runs without context
- **WHEN** a request targets a tenant-facing route and no valid active tenant context is established
- **THEN** tenant-local authentication and authorization are not executed and no tenant-owned data is accessed

### Requirement: Fail Closed On Missing Or Unresolved Tenant Context
The system SHALL deny a tenant-facing request without exposing tenant-owned data when tenant context is missing or unresolved.

#### Scenario: Tenant context missing
- **WHEN** a tenant-facing route is requested and no tenant context has been established
- **THEN** the request is denied with a non-tenant-revealing response and no tenant-local authentication, authorization, or tenant-owned data access occurs

#### Scenario: Tenant could not be resolved
- **WHEN** a tenant-facing route is requested but tenant resolution produced no tenant
- **THEN** the request fails closed and no tenant-owned data is exposed

### Requirement: Fail Closed On Inactive Tenant Context
The system SHALL deny a tenant-facing request without exposing tenant-owned data when the resolved tenant is not in an active lifecycle state.

#### Scenario: Resolved tenant is inactive
- **WHEN** a tenant-facing route is requested and the resolved tenant is suspended, archived, deleted, or otherwise not active
- **THEN** the request is denied with a non-tenant-revealing response and no tenant-local authentication, authorization, or tenant-owned data access occurs

### Requirement: Fail Closed On Ambiguous Tenant Context
The system SHALL deny a tenant-facing request without exposing tenant-owned data when tenant context is ambiguous.

#### Scenario: Multiple conflicting tenants resolved
- **WHEN** a tenant-facing route is requested and resolution yields more than one conflicting tenant identifier
- **THEN** the request fails closed, no single tenant is silently selected, and no tenant-owned data is exposed

### Requirement: Fail Closed When Tenant Context Cannot Be Established
The system SHALL deny a tenant-facing request without exposing tenant-owned data when the resolved tenant's context cannot be established.

#### Scenario: Tenant database or metadata unavailable
- **WHEN** a tenant-facing route is requested for a resolved tenant whose database or metadata cannot be opened or initialized
- **THEN** the request fails closed, tenant-scoped access does not fall back to the central database or a default tenant, and no tenant-owned data is exposed

### Requirement: Tenant-Local Checks Scoped To Active Tenant
The system SHALL scope tenant-local identity, membership, role, and permission checks reached through a tenant-facing route to the active resolved tenant only.

#### Scenario: Tenant-local lookup uses the active tenant
- **WHEN** a tenant-local identity, membership, role, or permission check is performed on a tenant-facing route
- **THEN** the check is evaluated only against the active tenant context's tenant

#### Scenario: Participation in one tenant does not carry to another
- **WHEN** a tenant user participates in tenant A and a request resolves tenant B
- **THEN** tenant A membership, identity, roles, or permissions grant no access, identity continuity, or authorization in tenant B

### Requirement: Tenant-Facing Routes Not Registered As Back Office Or Central Platform Routes
The system SHALL ensure tenant-facing routes are not registered as Back Office routes or central platform operation flows, and tenant-local state SHALL NOT grant Back Office or platform-level capabilities.

#### Scenario: Tenant-facing route absent from Back Office route set
- **WHEN** the registered route set is inspected
- **THEN** no tenant-facing route is registered as a Back Office or central platform operation route

#### Scenario: Tenant-local authorization does not grant platform capability
- **WHEN** a tenant-local role or permission is evaluated
- **THEN** it does not grant Back Office access or platform-level operational capabilities, and platform-operator privileges are not inherited from tenant-local identity, membership, role, or permission state

### Requirement: No Cross-Tenant Behavior Via Implicit Tenant-Facing Shared State
The system SHALL NOT introduce cross-tenant behavior through implicit tenant-facing shared state.

#### Scenario: Shared state does not leak tenant context
- **WHEN** consecutive tenant-facing requests resolve different tenants
- **THEN** no tenant context, tenant-local identity, or tenant-owned data from one request is reused for another via implicit shared or static state
