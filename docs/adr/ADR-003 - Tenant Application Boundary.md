# ADR-003: Tenant Application Boundary

## Status

Accepted

Date: 2026-06-09

## Owner

This ADR governs tenant-facing runtime behavior in applications built on this tenancy foundation.
Tenant-facing routing, authentication, authorization, membership, tenant-local identity use, and
tenant-facing workflows must integrate with this boundary instead of redefining where tenant-scoped
application behavior belongs.

## Decision

The application will provide a dedicated tenant-facing application boundary for tenant-scoped users,
membership, authorization, configuration, data, and business workflows. Tenant-facing application
areas are tenant-scoped by default and must run with an explicit tenant context.

Tenant-facing application behavior operates in tenant context by default. Tenant context is required
before tenant-local identity, membership, authorization, or tenant-owned data access is evaluated.

Tenant-facing routes must have an explicit tenant-facing route boundary. They must operate inside a
resolved tenant context and must not be reachable as platform Back Office routes or central platform
operation flows.

This ADR defines the reusable tenant-facing runtime boundary for the multitenant foundation. Concrete
tenant-facing URL structure, workspace naming, login flow, navigation, and product-specific tenant
application areas are intentionally left to consuming applications or later project-specific ADRs,
provided they preserve this boundary's tenant-context and authorization invariants.

Tenant-facing authentication must resolve tenant context before identifying, authenticating, or
authorizing a tenant-local user. Tenant-local identities, memberships, roles, and permissions follow
ADR-001's tenant-local identity model.

Tenant-local users, roles, memberships, and permissions must not grant platform-level operational
capabilities or Back Office access. Platform-operator privileges must not be inherited from
tenant-local identity, membership, role, or permission state.

Participation in multiple tenants follows ADR-001's tenant-local identity model. Participation in
one tenant does not imply participation, authorization, or identity continuity in another tenant.

Tenant-facing self-service operations belong inside the tenant-facing application boundary only when
they are initiated by tenant-local users, operate within one resolved tenant context, and are
authorized by tenant-local rules. Self-service operations must not become implicit platform
operations, cross-tenant operations, or Back Office workflows.

Tenant-owned application data and central platform metadata follow ADR-001's central-vs-tenant data
ownership rules. Tenant-facing application ownership must not redefine those storage boundaries.

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
* Tenant-facing routes require resolved tenant context before tenant-local authentication,
  authorization, or tenant-owned data access.
* Tenant-facing routes must fail closed when tenant context is missing, unresolved, inactive,
  ambiguous, or cannot be established. Tenant-local authentication and authorization must not execute
  in that state, and tenant-owned data must not be exposed.
* Tenant-facing routes must not be registered as Back Office routes or central platform operation
  flows.
* Tenant-local users, identities, memberships, roles, and permissions must not grant Back Office
  access or platform-level operational capabilities.
* Tenant-facing authentication, password reset, MFA, roles, and permissions follow ADR-001's
  tenant-local identity model unless a later ADR introduces a different identity model.
* Tenant-facing self-service operations must remain scoped to one resolved tenant and must not
  perform implicit cross-tenant, platform, or Back Office operations.
* Tenant-owned application data remains tenant-owned data and must not be stored in central storage
  unless ADR-001 or a later ADR explicitly defines the component as cross-tenant infrastructure.
* Cross-tenant behavior must not be introduced through implicit tenant-facing shared state.
* This ADR does not define concrete URL topology, product workspace structure, login experience,
  global identity, tenant federation, cross-tenant collaboration, or organization-level
  authorization.

## Validation

Important invariants should be enforced through route registration review, tenant-boundary tests,
authorization tests, tenant-context tests, and code review for tenant-facing workflow changes.

Expected validation includes:

* The application must make tenant-facing routes distinguishable from Back Office and central
  platform routes.
* Tests or route inspection must verify that tenant-facing routes require tenant resolution and do
  not execute tenant-local authentication or authorization without resolved tenant context.
* Tests must verify that tenant-facing routes fail closed without exposing tenant-owned data when
  tenant context is missing, unresolved, inactive, ambiguous, or cannot be established.
* Authorization tests must verify that tenant-local roles and permissions do not grant Back Office
  access or platform-level operational capabilities.
* Tests must verify that tenant-local identity, membership, role, and permission checks are scoped to
  the active tenant.
* Code review must reject tenant-facing workflows that perform implicit cross-tenant access, rely on
  shared tenant-local identity state across tenants, bypass tenant context, or perform platform
  operations through tenant-facing authorization.

## Revisit When

Revisit this decision if the foundation must provide a concrete tenant-facing route topology, if a
global or federated identity model replaces ADR-001's tenant-local identity model, or if
tenant-facing features must intentionally span multiple tenants inside one user workflow.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy Architecture.md>)
* [ADR-002: Back Office](<ADR-002 - Back Office.md>)
