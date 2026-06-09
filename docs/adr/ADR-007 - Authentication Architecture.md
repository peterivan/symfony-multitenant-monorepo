# ADR-007: Authentication Architecture

## Status

Accepted

Date: 2026-06-09

## Owner

The authentication boundary owns this decision. Platform-operator authentication, tenant-user
authentication, authentication session scope, identity-store selection, and integration points with
tenant resolution must integrate with this boundary instead of redefining identity ownership or
authorization rules.

## Decision

The application uses separate authentication boundaries for platform operators and tenant users.

Platform operators authenticate against platform-owned identities in central platform storage.
Platform authentication belongs to the Back Office boundary defined by ADR-002 and runs in central
context.

Tenant users authenticate against tenant-owned identities in tenant storage. Tenant authentication
belongs to the tenant-facing application boundary defined by ADR-003 and runs inside an explicit
tenant context.

The application does not provide a shared authentication boundary by default. Central platform
authentication and tenant authentication are separate concerns. Authentication must follow the
identity ownership model defined by ADR-006.

Tenant resolution must occur before tenant-user authentication is evaluated. A tenant user cannot
authenticate without a resolved tenant context. Tenant authentication must not inspect tenant-local
identity records, credentials, sessions, or authentication state until tenant context has been
established and the tenant is eligible for tenant-facing access. Tenant authentication must use the
tenant identity store selected by the resolved tenant context, not a default tenant, stale tenant
context, central identity store, or shared tenant-user identity source. This requirement does not
prescribe tenant-facing URL topology, login-page structure, tenant-selection UX, or product-specific
application flow.

Authentication must not silently fall back between central and tenant identity stores. Failed
platform authentication must not attempt tenant authentication as a fallback. Failed tenant
authentication must not attempt platform authentication as a fallback. Missing, unresolved,
inactive, ambiguous, or unavailable tenant context must fail closed for tenant authentication.

Matching identity attributes across boundaries do not affect authentication. A platform operator must
not authenticate as a tenant user merely because a tenant user has the same email address or other
identity attribute. A tenant user must not authenticate as a platform operator merely because a
platform operator has the same email address or other identity attribute. The same email address may
exist independently in multiple tenants, and tenant authentication remains scoped to the resolved
tenant.

Authentication state and sessions are scoped to their authentication boundary. Platform
authentication state is central-context state for Back Office access. Tenant authentication state is
tenant-scoped state for one resolved tenant. Central and tenant authentication state must remain
isolated and must not imply each other. Tenant authentication state must identify or be otherwise
bound to the tenant context for which the tenant user authenticated. Tenant authentication state must
be rejected when it does not match the currently resolved tenant context.

Tenant lifecycle state is part of tenant authentication eligibility. Tenant-facing authentication
must not proceed for tenants that are missing, unresolved, inactive, suspended, archived, deleted, or
otherwise not eligible for tenant-facing access. Tenant lifecycle eligibility applies both when
establishing tenant authentication state and when using existing tenant authentication state.
Concrete user-facing failure behavior is outside this ADR.

Authentication proves or establishes control of an identity within its boundary. Authorization
decides what that authenticated identity may do within that boundary. This ADR does not define roles,
permissions, authorization policies, RBAC, ABAC, capability models, MFA implementation details,
password complexity rules, password reset workflows, OIDC, SAML, LDAP, federation protocols, or
login-page UX.

## Why

ADR-006 defines separate identity boundaries for platform operators and tenant users. Authentication
must preserve that separation. A central platform identity authenticates in central context for Back
Office access. A tenant-owned identity authenticates in tenant context for tenant-facing access.

ADR-001 requires tenant context before tenant-scoped data access and defines tenant-local identities
as tenant-owned data. ADR-003 requires tenant context before tenant-local identity, membership,
authorization, or tenant-owned data access is evaluated. Tenant authentication therefore must be
tenant-context-first: the selected tenant determines which tenant identity store is authoritative.

ADR-002 defines Back Office as a central operational surface for platform operators. Back Office does
not require tenant resolution for entry and must not rely on tenant-local users, roles, memberships,
or permissions for access.

The main alternative is a shared authentication boundary that can authenticate a person first and
select a platform or tenant context later. That model can support global accounts, account switching,
and federation, but it contradicts the current identity boundary model and introduces central
identity continuity that the platform does not provide by default.

This decision intentionally favors explicit authentication boundaries over convenience across
platform and tenant surfaces. It makes cross-boundary access, federation, impersonation, and
delegated support workflows future architectural decisions instead of accidental behavior.

## Consequences And Invariants

* Platform operators authenticate against platform-owned identities in central platform storage.
* Platform authentication runs in central context and is the authentication boundary for Back Office.
* Back Office entry must not require tenant resolution.
* Tenant users authenticate against tenant-owned identities in tenant storage.
* Tenant authentication runs inside the resolved tenant context required by ADR-003.
* Tenant resolution must complete before tenant-user authentication is evaluated.
* Tenant users must not authenticate without a resolved tenant context.
* Tenant authentication must fail closed when tenant context is missing, unresolved, inactive,
  suspended, archived, deleted, ambiguous, unavailable, or cannot be established.
* Tenant authentication must use the tenant identity store selected by the resolved tenant context.
* Tenant authentication must not use a default tenant, stale tenant context, central identity store,
  or shared tenant-user identity source.
* Authentication must not silently fall back between central and tenant identity stores.
* Platform authentication must not authenticate against tenant-owned identity stores.
* Tenant authentication must not authenticate against central platform-owned identity stores.
* A platform-operator identity must not imply tenant-user authentication or tenant-facing access.
* A tenant-user identity must not imply platform-operator authentication or Back Office access.
* Matching email addresses or other identity attributes across central and tenant boundaries must not
  be used as proof of authentication in another boundary.
* Matching email addresses or other identity attributes across tenants must not be used as proof of
  authentication in another tenant.
* Platform authentication state and tenant authentication state must remain isolated.
* Platform authentication state is scoped to central Back Office access.
* Tenant authentication state is scoped to the tenant context for which the tenant user
  authenticated.
* Tenant authentication state must be rejected when the current resolved tenant context does not
  match the tenant context for which that state was established.
* Existing tenant authentication state must not remain valid when the tenant is no longer eligible
  for tenant-facing access.
* Tenant authentication state must not grant access to another tenant.
* Tenant authentication state must not grant Back Office access.
* Platform authentication state must not grant tenant-user identity or ordinary tenant-facing
  application access.
* Platform and tenant authentication may use separate authentication infrastructure, providers, or
  runtime wiring, provided those mechanisms preserve this ADR's boundary and isolation invariants.
* Tenant-facing support, impersonation, delegated administration, federation, and cross-boundary
  account switching require later ADRs when they change authentication boundary behavior.
* This ADR does not define credentials, authenticators, login flows, session storage implementation,
  MFA, password reset behavior, roles, permissions, authorization policies, identity federation,
  support impersonation, service accounts, or tenant directory synchronization.

## Validation

Important invariants should be enforced through architecture review, authentication tests,
tenant-boundary tests, Back Office access tests, session-scope tests, and code review of
authentication changes.

Expected validation includes:

* Tests must verify that Back Office authentication uses central platform-owned identities and does
  not require tenant resolution.
* Tests must verify that tenant authentication does not execute until tenant context is resolved.
* Tests must verify that tenant authentication uses the identity store selected by the resolved
  tenant context and does not use a default tenant, stale tenant context, central identity store, or
  shared tenant-user identity source.
* Tests must verify that tenant authentication fails closed when tenant context is missing,
  unresolved, inactive, suspended, archived, deleted, ambiguous, unavailable, or cannot be
  established.
* Tests must verify that tenant users cannot authenticate into another tenant because the same email
  address or other identity attribute exists there.
* Tests must verify that tenant users cannot authenticate into Back Office through tenant-local
  identity state.
* Tests must verify that platform operators cannot authenticate as tenant users through
  platform-owned identity state.
* Tests must verify that platform authentication state and tenant authentication state do not imply
  each other.
* Tests must verify that tenant authentication state is scoped to one tenant and does not grant
  access to another tenant.
* Tests must verify that tenant authentication state is rejected when used with a different resolved
  tenant context.
* Tests must verify that existing tenant authentication state is rejected after the tenant becomes
  ineligible for tenant-facing access.
* Code review must reject authentication changes that introduce silent fallback between central and
  tenant identity stores.
* Code review must reject shared authentication behavior that introduces a central identity or
  cross-tenant authentication model without a superseding ADR.

## Revisit When

Revisit this decision if a shared identity model replaces ADR-006, if identity federation becomes the
primary authentication model, if tenant users must authenticate before tenant selection, if
cross-tenant account switching becomes a primary application workflow, if support impersonation
requires a formal cross-boundary authentication model, or if platform-operator access becomes
tenant-delegated by default.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy Architecture.md>)
* [ADR-002: Back Office](<ADR-002 - Back Office.md>)
* [ADR-003: Tenant Application Boundary](<ADR-003 - Tenant Application Boundary.md>)
* [ADR-006: Identity Boundary Model](<ADR-006 - Identity Boundary Model.md>)
