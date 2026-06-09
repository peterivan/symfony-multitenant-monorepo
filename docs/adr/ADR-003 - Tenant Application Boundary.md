# ADR-003: Tenant Application Boundary

## Status

Accepted

Date: 2026-06-09

## Owner

The tenant application boundary owns this decision. Tenant-facing routing, authentication,
authorization, membership, navigation, tenant-local identity use, and tenant-facing workflows must
integrate with this boundary instead of redefining where tenant-scoped application behavior belongs.

## Decision

The application will provide a dedicated tenant-facing application boundary for tenant-scoped users,
membership, authorization, configuration, data, and business workflows. Tenant-facing application
areas are tenant-scoped by default and must run with an explicit tenant context.

Tenant-facing application behavior operates in tenant context by default. Tenant context is required
before tenant-local identity, membership, authorization, or tenant-owned data access is evaluated.

Tenant-facing routes must have an explicit tenant-facing route boundary. They must operate inside a
resolved tenant context and must not be reachable as platform Back Office routes or central platform
operation flows.

Tenant-facing authentication must resolve tenant context before identifying, authenticating, or
authorizing a tenant-local user. Tenant-local identities, memberships, roles, and permissions apply
only inside the owning tenant, as defined by ADR-001.

Tenant-local users, roles, memberships, and permissions must not grant platform-level operational
capabilities or Back Office access. Platform-operator privileges must not be inherited from
tenant-local identity, membership, role, or permission state.

The same email address, login name, or person may participate in multiple tenants. Under ADR-001's
tenant-local identity model, that participation is represented by tenant-local identity or membership
state. Participation in one tenant does not imply participation, authorization, or identity
continuity in another tenant.

Tenant-facing self-service operations belong inside the tenant-facing application boundary only when
they are initiated by tenant-local users, operate within one resolved tenant context, and are
authorized by tenant-local rules. Self-service operations must not become implicit platform
operations, cross-tenant operations, or Back Office workflows.

Tenant-owned application data lives in tenant databases as defined by ADR-001. Central platform
metadata, platform operators, tenant registry data, provisioning state, and other cross-tenant
infrastructure data remain outside tenant-facing application ownership.

Shared cross-tenant application areas, federated tenant identity, global tenant-user identity, and
organization-spanning authorization are outside the scope of this decision. Introducing any of those
models requires a later ADR.

## Why

The application needs tenant-facing behavior to remain aligned with tenant isolation. Without a
clear tenant application boundary, tenant membership, authorization, data ownership, and user
workflows become ambiguous, increasing the risk of accidental cross-tenant access or privilege
leakage into platform operations.

ADR-001 defines the tenancy model, tenant context lifecycle, tenant-local identity ownership, and
central-vs-tenant data ownership. ADR-002 defines the Back Office platform-operations boundary. This
ADR defines how tenant-facing application behavior consumes those boundaries: tenant work is scoped
to one tenant, tenant-local authorization remains tenant-local, and tenant-facing features do not
become platform operations.

The main alternative is to use global user identities and organization-spanning authorization as the
primary application model. That can simplify cross-tenant user experience, but it weakens the default
tenant isolation model and makes authorization harder to reason about before the application has a
deliberate global identity design.

## Consequences And Invariants

* Tenant-facing application routes must have an explicit tenant-facing route boundary.
* Tenant-facing application behavior operates in tenant context by default.
* Tenant-facing routes require resolved tenant context before tenant-local authentication or
  authorization.
* Tenant-facing routes must fail closed when tenant context is missing, unresolved, inactive, or
  ambiguous.
* Tenant-facing routes must not be registered as Back Office routes or central platform operation
  flows.
* Tenant-local users, identities, memberships, roles, and permissions apply only within the owning
  tenant.
* Tenant-local users, identities, memberships, roles, and permissions must not grant Back Office
  access or platform-level operational capabilities.
* Tenant-facing authentication, password reset, MFA, roles, and permissions follow ADR-001's
  tenant-local identity model unless a later ADR introduces a different identity model.
* Under ADR-001's tenant-local identity model, a person participating in multiple tenants requires
  tenant-local identity or membership state in each tenant.
* Tenant-facing self-service operations must remain scoped to one resolved tenant and must not
  perform implicit cross-tenant, platform, or Back Office operations.
* Cross-tenant access, inspection, reporting, synchronization, imports, exports, and operational
  actions must not occur through tenant-facing implicit shared state.
* Tenant imports and exports must explicitly define tenant scope.
* Tenant-owned application data remains tenant-owned data and must not be stored in central storage
  unless ADR-001 or a later ADR explicitly defines the component as cross-tenant infrastructure.
* This ADR does not define Back Office behavior, platform-operator authorization, tenant lifecycle
  administration, global identity, tenant federation, cross-tenant collaboration, organization-level
  authorization, audit architecture, or the concrete frontend build system.

## Validation

Important invariants should be enforced through route registration review, tenant-boundary tests,
authorization tests, tenant-context tests, and code review for tenant-facing workflow changes.

Expected validation includes:

* Tests or route inspection must verify that tenant-facing application routes are exposed through the
  defined tenant-facing route boundary.
* Tests or route inspection must verify that tenant-facing routes require tenant resolution and do
  not execute tenant-local authentication or authorization without resolved tenant context.
* Tests must verify that tenant-facing routes fail closed when tenant context is missing, unresolved,
  inactive, or ambiguous.
* Authorization tests must verify that tenant-local roles and permissions do not grant Back Office
  access or platform-level operational capabilities.
* Tests must verify that tenant-local identity, membership, role, and permission checks are scoped to
  the active tenant.
* Code review must reject tenant-facing workflows that perform implicit cross-tenant access, rely on
  shared tenant-local identity state across tenants, bypass tenant context, or perform platform
  operations through tenant-facing authorization.

## Revisit When

Revisit this decision if global user identity becomes a primary application requirement,
organization-spanning authorization becomes necessary, tenant self-service expands into
platform-delegated operations, cross-tenant collaboration becomes a core product capability, or the
tenant-facing route boundary becomes unsuitable.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy architecture.md>)
* [ADR-002: Back Office](<ADR-002 - Back Office.md>)
