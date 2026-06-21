## Context

ADR-001 establishes the tenancy layer (tenant resolution, tenant context lifecycle, database selection) and requires that tenant context be explicitly initialized per execution unit, never implicitly inherited across process/async boundaries, and that CLI commands / workers / jobs declare central vs single-tenant vs cross-tenant context. ADR-003 establishes the tenant-facing application boundary: tenant-facing routes must have an explicit boundary, require resolved tenant context before tenant-local authn/authz/tenant-owned-data access, fail closed when context is missing/unresolved/inactive/ambiguous/unestablishable, and must not be reachable as Back Office or central platform routes.

The codebase already has `App\Tenant\TenantContext`, `App\Tenant\TenantResolver`, and `App\EventSubscriber\TenantSubscriber` wired at kernel.request priority 20, which resolves and establishes tenant context for HTTP requests. What is missing is (1) an *enforcement* boundary that makes resolved tenant context a precondition for tenant-facing routes and fails closed otherwise, and (2) a declaration mechanism so non-HTTP execution units (commands, workers, jobs) state their context and async boundaries do not leak tenant context.

This change specifies the BOUNDARY and fail-closed enforcement only. The authentication and authorization *mechanisms* (login, password reset, MFA, role/permission evaluation logic) are specced in separate changes; here we only guarantee they cannot run without a valid active tenant context on tenant-facing routes, and that tenant-local checks are scoped to the active tenant.

## Goals / Non-Goals

**Goals:**
- Provide an explicit, inspectable way to classify a route as tenant-facing vs central/Back Office, and guarantee tenant-facing routes are never registered as Back Office / central platform routes.
- Make resolved, active, unambiguous tenant context a hard precondition for tenant-facing routes, enforced before tenant-local authentication, authorization, or any tenant-owned data access.
- Fail closed (deny, without exposing tenant-owned data) whenever tenant context is missing, unresolved, inactive, ambiguous, or cannot be established for a tenant-facing route.
- Require tenant-local identity, membership, role, and permission checks reached via tenant-facing routes to be scoped to the active resolved tenant, and ensure tenant-local state grants no Back Office / platform capability.
- Require every CLI command, worker, and background job to declare central / single-tenant / cross-tenant execution context.
- Guarantee async boundaries strip active tenant context by default and re-establish it only from explicit routing metadata / job attributes / command options, failing closed on tenant-scoped work whose tenant cannot be re-established.

**Non-Goals:**
- Defining concrete tenant-facing URL topology, workspace naming, login UX, or navigation (left to consuming apps / later ADRs).
- Implementing the authentication, password-reset, MFA, role, or permission mechanisms themselves (separate changes).
- Defining the Back Office surface internals (ADR-002 / its own change).
- Introducing global/federated identity, cross-tenant collaboration, or organization-spanning authorization.
- Choosing a specific message broker; PostgreSQL-only infra, transport choice is incidental.

## Decisions

- **Explicit boundary marker over implicit URL conventions.** A route is classified tenant-facing through an explicit marker (route default / attribute / controller marker) rather than inferred from path strings, so classification is inspectable and testable and so a route cannot be both tenant-facing and Back Office. This satisfies ADR-003's "explicit tenant-facing route boundary" and "distinguishable from Back Office and central platform routes".
- **Enforcement runs after resolution, before security.** Tenant resolution stays in `TenantSubscriber` (prio 20). A separate enforcement listener runs at a lower kernel.request priority — after resolution, before Symfony's firewall/authentication — so tenant-local authentication and authorization never execute without a valid active tenant context. The tenancy layer remains the only component that creates/replaces/clears tenant context (ADR-001).
- **Fail closed = deny without leaking.** On a tenant-facing route with missing/unresolved/inactive/ambiguous/unestablishable context, the request is denied with a non-tenant-revealing response (e.g. 404/403 with no tenant-owned data, no tenant-local authn attempted). "Ambiguous" covers multiple conflicting resolved tenants; "unestablishable" covers tenant DB / metadata that cannot be opened.
- **Tenant-local checks scoped to active tenant.** Any tenant-local identity / membership / role / permission lookup reached through a tenant-facing route is resolved against the active tenant context's tenant only; participation in one tenant implies nothing in another (ADR-001/003).
- **Execution-context declaration is mandatory and explicit.** Commands, workers, and jobs declare context via an attribute/interface (central / single-tenant / cross-tenant). Undeclared execution units fail closed rather than defaulting to any tenant. Single-tenant and cross-tenant declarations require explicit tenant selection / iteration; they must not fall back to a default or stale tenant.
- **Async boundaries strip then re-establish from metadata only.** A Messenger middleware (or equivalent) clears active tenant context when dispatching and on the consuming side establishes tenant context only from explicit stamp/metadata. Tenant-scoped messages without resolvable tenant metadata fail closed; cross-tenant jobs establish and dispose context per tenant iteration including failure paths (ADR-001).
- **No cross-tenant behavior via implicit shared state.** Shared/static state must not carry tenant context across execution units or async boundaries; context is established per execution unit and cleared when it ends, including failures.

## Risks / Trade-offs

- **Priority ordering fragility.** Enforcement must reliably run after resolution and before the firewall. Mitigation: pin and test kernel.request priorities; add a boundary test asserting ordering and that authn does not run without context.
- **Misclassified routes.** A tenant-facing route accidentally lacking the boundary marker would bypass enforcement. Mitigation: fail closed by default for routes lacking an explicit central/Back Office classification where feasible, plus route-inspection tests asserting tenant-facing routes carry the marker and are absent from Back Office route sets.
- **Information disclosure via error responses.** Distinct error codes for "inactive" vs "missing" tenant could leak tenant existence. Mitigation: uniform non-revealing fail-closed response; only diagnostic logs (central, minimal PII) distinguish causes.
- **Async metadata gaps.** Existing/legacy dispatch paths might not stamp tenant metadata. Mitigation: strip-by-default means the unsafe default is "no tenant" (fail closed for tenant-scoped handlers), never "inherit the dispatcher's tenant".
- **Developer friction from mandatory declaration.** Requiring every command/worker/job to declare context adds boilerplate. Mitigation: a clear attribute/interface and a test/static check that flags undeclared units; central context is a valid explicit declaration for platform tooling.
