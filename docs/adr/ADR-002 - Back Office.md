# ADR-002: Back Office

## Status

Accepted

Date: 2026-06-08

## Owner

The Back Office boundary owns this decision. Runtime routing, authorization, navigation, layouts,
audit requirements for platform operations, and operational tooling that expose platform-level
capabilities must integrate with this boundary instead of redefining where platform operations
belong.

## Decision

The platform will provide a dedicated Back Office operational surface under `/bo` for platform-level
operations. Back Office is an internal operational surface for platform operators; it is not part of
tenant-facing application areas and must not host tenant business workflows.

Filesystem placement and frontend technology do not define the boundary. The boundary is defined by
runtime route exposure, authorization, tenant-context behavior, data access rules, and audit
requirements.

Back Office routes are central-context only. They must not be reachable as tenant-subdomain routes,
tenant-facing routes, or tenant-scoped application flows.

Back Office access is granted through platform-operator authorization. Tenant-local users, tenant
memberships, tenant roles, and tenant-local permissions must not grant Back Office access.
Platform operators may inspect tenant environments only through explicit operational actions.
Tenant-local identities must not automatically inherit platform-operator privileges.

Back Office owns operational capabilities that configure, inspect, support, maintain, or administer
the platform as a whole. Tenant-facing business workflows belong outside Back Office, even when those
workflows are performed by privileged tenant users.

Tenant-facing self-service operations, when introduced, must remain outside Back Office and must run
within tenant-facing authorization and tenant-context boundaries.

Back Office is the platform operations surface for tenant lifecycle administration. It may create
tenant records, provision tenant databases, initialize tenant baseline data, change tenant lifecycle
state, and run other operational tasks that act on the central registry or on explicitly selected
tenant infrastructure.

Tenant lifecycle operations do not all require tenant context. Operations against the central tenant
registry run in central context. Operations that initialize, migrate, seed, inspect, or maintain a
tenant database may establish tenant context only from explicit platform-operator intent and only for
the bounded operation being performed.

Back Office must remain separated from tenant-facing areas by route namespace, URL boundary, layout,
navigation, authorization model, data access rules, and operator-focused UX conventions.

Any Back Office capability that reads, derives, exports, mutates, or deletes tenant-owned data must
make the selected tenant and data scope explicit. Back Office must not silently act inside an
ambiguous tenant context or rely on tenant-local authorization to justify platform-level access.

## Why

The application needs an internal operational surface for platform operators whose responsibilities,
permissions, data scope, and interaction patterns differ from tenant-facing workflows.

Mixing platform operations into tenant-facing application areas would blur ownership boundaries, make
authorization harder to reason about, and increase the risk of exposing privileged operational
capabilities to tenant users. Keeping Back Office central-only preserves the data ownership model in
ADR-001 and keeps tenant-local work separate from platform operations.

The main alternative is to expose administrative capabilities inside tenant-facing application areas
behind additional privileged tenant roles. That would simplify navigation for some support workflows,
but it would couple platform administration to tenant-local identity and authorization. The
application uses an explicit platform-operator boundary because the cost of accidental privilege
expansion is higher than the cost of maintaining a separate operational surface.

## Consequences And Invariants

* Back Office is exposed at runtime under `/bo`.
* Back Office may be implemented as a separate application.
* Back Office routes are central-context only and must not be registered as tenant-subdomain routes.
* Back Office starts in central context without an active tenant context.
* Tenant business workflows must not be implemented in Back Office.
* Tenant-local users, roles, memberships, and permissions must not grant Back Office access.
* Platform operators may inspect tenant environments only through explicit operational actions.
* Tenant-local identities must not automatically inherit platform-operator privileges.
* Back Office actions must use platform-operator authorization.
* Back Office must not implicitly inherit tenant context from tenant-facing requests, sessions,
  workers, or navigation state.
* Back Office may create, initialize, suspend, archive, delete, inspect, or maintain tenants only
  through explicit platform operations.
* Tenant lifecycle and tenant-impacting operations must execute through explicit platform operations
  governed by platform-operator authorization, tenant-context rules, and audit requirements.
* Tenant registry operations run in central context.
* Tenant database initialization, migration, seeding, inspection, and maintenance may establish
  tenant context only for the bounded operation being performed.
* Tenant context must be explicit whenever Back Office inspects, exports, mutates, deletes, or
  otherwise affects tenant-owned data.
* Tenant context established by a Back Office operation must be scoped to that operation and cleared
  when the operation ends, including failure cases.
* Tenant lifecycle changes, cross-tenant operations, tenant-owned data access or mutation, and
  security-sensitive platform configuration changes must be audited.
* Audit coverage for those operations is mandatory. Implementations must not defer, skip, or make
  audit recording optional for those operations.
* Audit record structure, audit layers, and audit implementation mechanisms are outside this ADR.
* Back Office navigation and layout evolve independently from tenant-facing navigation.
* Back Office implementation may use separate build or deployment mechanics, but those mechanics must
  not weaken the runtime routing, authorization, tenant-context, or audit invariants.
* Cross-tenant operations require explicit safeguards because Back Office can legitimately operate
  across tenant boundaries.
* This ADR does not define tenant-facing application behavior, tenant-local authorization, global
  identity, support workflow details, analytics implementation, concrete UI component architecture,
  or the frontend build system.

## Validation

Important invariants should be enforced through route registration review, authorization tests,
tenant-boundary tests, audit assertions for tenant-impacting and security-sensitive operations, and
code review of operational tooling.

Expected validation includes:

* Tests or route inspection must verify that Back Office routes are exposed under `/bo` and are not
  exposed as tenant-subdomain routes.
* Tests or route inspection must verify that Back Office entry does not require tenant resolution and
  does not initialize tenant context from the request host.
* Authorization tests must verify that tenant-local roles and permissions cannot grant Back Office
  access.
* Tests for Back Office actions touching tenant-owned data must verify explicit tenant selection and
  fail-closed behavior when tenant scope is missing or ambiguous.
* Tests for tenant lifecycle operations must verify that tenant context is established only from
  explicit operator intent and is cleared after success and failure.
* Code review must reject Back Office features that implement tenant business workflows, bypass
  platform-operator authorization, or depend on implicit tenant context.
* Code review must reject tenant lifecycle changes, cross-tenant operations, tenant-owned data access
  or mutation, and security-sensitive platform configuration changes without audit coverage.

## Revisit When

Revisit this decision if platform operations become tenant-delegated by default, if a global identity
model replaces separate platform-operator and tenant-local identities, if dedicated deployments make
central Back Office routing unsuitable, or if support workflows require a deliberately designed
tenant-embedded operational surface.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy Architecture.md>)
