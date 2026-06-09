# ADR-006: Identity Boundary Model

## Status

Accepted

Date: 2026-06-09

## Owner

The identity boundary owns this decision. Platform-operator identity ownership, tenant-user identity
ownership, identity boundary separation, and integration points with authentication and authorization
must integrate with this boundary instead of introducing a shared identity model by default.

## Decision

The application uses separate identity boundaries for platform operators and tenant users.

Platform operators are platform-owned identities. They live in central platform storage and access
the Back Office boundary defined by ADR-002. Platform operators are a central platform concern, not a
tenant concern.

Tenant users are tenant-owned identities. They live in tenant storage and access tenant-facing
application surfaces defined by ADR-003. Tenant users are a tenant concern, not a central platform
identity concern.

The application does not use a central identity plus tenant membership model by default. Tenant
access is not established through membership in a central identity system. Tenant user existence,
authentication, authorization, profile state, and tenant-facing access are scoped to the selected
tenant unless a later ADR explicitly introduces a different identity model. This does not prohibit
tenant-local membership, assignment, or user-to-tenant relationship records inside a tenant boundary.

The same email address or other identity attribute may exist independently in multiple tenants.
Matching identity attributes across tenants must not imply shared identity, tenant participation,
access, authorization, or identity continuity across those tenants.

There is no platform-level "same person" concept by default. The same human may separately exist as a
platform-operator identity and as one or more tenant-user identities. Those identities remain
separate, do not imply each other, and must not be merged by matching identity attributes.

Authentication proves or establishes control of an identity within its boundary. Authorization
decides what that identity may do within that boundary. This ADR defines identity ownership and
boundary separation only; it does not define login flows, sessions, credentials, MFA, password reset
behavior, role systems, permission systems, or policy engines.

## Why

The application is built around strong tenant isolation: database-per-tenant storage, tenant-owned
data, tenant-context-first tenant-facing behavior, and a separate central Back Office boundary.
Tenant-owned identities align with that model because tenant user existence and access remain local
to the tenant database and tenant application boundary.

ADR-001 defines tenant-local identity ownership as intentional. ADR-002 defines Back Office as a
central platform-operator surface. ADR-003 defines tenant-facing behavior as tenant-context-first and
tenant-local. This ADR makes those identity boundaries explicit without introducing a central
identity architecture that the current platform does not require.

The main alternative is to introduce a central identity plus tenant membership model, where one
platform identity gains tenant access through memberships. That model can support cross-tenant user
experience and federation, but it introduces additional ownership questions, membership management,
invitation flows, cross-tenant identity continuity, and central identity operations. Those concerns
are not required by the current architecture and would weaken the default tenant-local identity
boundary.

This decision intentionally optimizes for tenant isolation over cross-tenant identity continuity.
Tenant-local identities make central account management, global profile updates, cross-tenant account
recovery, and future federation more expensive if those capabilities become required.

## Consequences And Invariants

* Platform operators are central platform identities.
* Platform-operator identities live in central platform storage.
* Platform operators access Back Office through the platform-operator boundary defined by ADR-002.
* Platform-operator identity must not be treated as tenant-user identity.
* Tenant users are tenant-owned identities.
* Tenant-user identities live in tenant storage.
* Tenant users access tenant-facing application surfaces through the tenant boundary defined by
  ADR-003.
* Tenant-user existence and access are scoped to the selected tenant.
* Tenant user existence in one tenant must not imply existence, access, authorization, or identity
  continuity in another tenant.
* The same email address or other identity attribute may exist independently in multiple tenants.
* Matching identity attributes across tenants must not be used as proof of shared identity or
  cross-tenant access.
* There is no platform-level "same person" concept by default.
* The same human may have a platform-operator identity and tenant-user identities; those identities
  remain separate and must not imply each other.
* Name changes, email changes, profile updates, and account recovery for tenant users are tenant-local
  concerns unless a later ADR introduces central identity lifecycle management.
* Tenant-local authentication and authorization must run inside the resolved tenant context required
  by ADR-003.
* Tenant-local roles, permissions, profiles, preferences, and workflow state belong to the tenant
  boundary and must not grant Back Office access.
* Platform-operator authorization does not imply tenant-user identity or tenant-facing application
  access. Any tenant-operational access by platform operators must occur through explicit platform
  operations governed by ADR-002.
* There is no default central tenant membership model.
* Tenant access must not depend on membership in a central identity system unless a later ADR
  explicitly introduces that model.
* Audit attribution must distinguish platform-operator identities, tenant-user identities, selected
  tenant context, and platform operations when those facts apply.
* This ADR does not define login flows, sessions, MFA, password reset behavior, credentials,
  invitation flows, role systems, permission systems, policy engines, identity federation,
  impersonation, delegated administration, service accounts, or tenant directory synchronization.

## Validation

Important invariants should be enforced through architecture review, data-model review, tenant access
tests, Back Office authorization tests, audit review, and code review of identity, authentication,
and authorization changes.

Expected validation includes:

* Code review must reject changes that introduce a central identity plus tenant membership model
  without a superseding ADR.
* Code review must reject tenant-facing access checks that depend on platform-operator identity or
  shared tenant-user identity state.
* Tests must verify that tenant users from one tenant do not gain access to another tenant because
  they share the same email address or other identity attribute.
* Tests must verify that tenant-local roles, permissions, profiles, or tenant-user identities do not
  grant Back Office access.
* Tests must verify that platform-operator identities do not automatically grant tenant-user
  identity, tenant-local authorization, or tenant-facing access.
* Data-model review must verify that platform-operator identities and tenant-user identities remain
  owned by their respective central and tenant boundaries.
* Audit review must verify that tenant-impacting and platform-operation records can distinguish
  platform-operator identity, tenant-user identity, selected tenant context, and platform operation
  scope when those facts apply.

## Revisit When

Revisit this decision if the application requires cross-tenant identity continuity, if tenant users
must participate in multiple tenants through one shared identity, if identity federation becomes the
primary identity model, if tenant-facing applications require centralized account management, or if
platform-operator access is redesigned to be tenant-delegated by default.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy Architecture.md>)
* [ADR-002: Back Office](<ADR-002 - Back Office.md>)
* [ADR-003: Tenant Application Boundary](<ADR-003 - Tenant Application Boundary.md>)
