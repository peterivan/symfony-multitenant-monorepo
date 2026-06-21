## Why

ADR-003 mandates an explicit tenant-facing application boundary: tenant-facing routes must require resolved tenant context before any tenant-local authentication, authorization, or tenant-owned data access, and must fail closed when context is missing, unresolved, inactive, ambiguous, or unestablishable. Today `TenantSubscriber` (kernel.request prio 20) resolves tenant context, but there is no enforced route boundary that *requires* it, no fail-closed gate for tenant-facing routes, and no guarantee tenant-facing routes are kept distinct from Back Office / central platform routes. ADR-001 further requires that CLI commands, workers, and background jobs declare their execution context (central / single-tenant / cross-tenant) and that tenant context is never implicitly inherited across async boundaries. Neither the route boundary nor the execution-context declaration mechanism exists yet, so tenant isolation invariants are unenforced.

## What Changes

- Introduce an explicit tenant-facing route boundary marker so a route is unambiguously classified as tenant-facing, central platform, or Back Office, and tenant-facing routes are never registered as Back Office / central platform routes.
- Add a fail-closed enforcement gate that runs after tenant resolution and before tenant-local authentication/authorization/tenant-owned-data access: tenant-facing routes are denied (without exposing tenant-owned data) when tenant context is missing, unresolved, inactive, ambiguous, or unestablishable.
- Require tenant-local identity, membership, role, and permission checks reached through tenant-facing routes to be scoped to the active resolved tenant; tenant-local state must not grant Back Office or platform-level capabilities.
- Introduce an execution-context declaration mechanism for CLI commands, workers, and background jobs: each declares whether it runs in central, single-tenant, or cross-tenant context.
- Enforce that async boundaries (Messenger messages, queues, scheduled jobs) strip active tenant context by default and re-establish tenant context only from explicit routing metadata / job attributes / command options — never via implicit shared state.
- Fail closed for undeclared or context-mismatched execution units, and for tenant-scoped async work whose tenant cannot be re-established from explicit metadata.

## Capabilities

### New Capabilities
- `tenant-request-boundary`: An explicit, classifiable tenant-facing route boundary that requires resolved, active, unambiguous tenant context before tenant-local authn/authz/tenant-owned-data access, fails closed otherwise, scopes tenant-local checks to the active tenant, and keeps tenant-facing routes distinct from Back Office / central platform routes. (→ specs/tenant-request-boundary/spec.md)
- `execution-context-declaration`: A declaration mechanism requiring CLI commands, workers, and background jobs to state their central / single-tenant / cross-tenant execution context, forbidding implicit tenant-context inheritance across async boundaries, and re-establishing tenant context only from explicit routing metadata. (→ specs/execution-context-declaration/spec.md)

### Modified Capabilities

## Impact

- Code: new tenant-facing route boundary marker/attribute and a fail-closed enforcement subscriber/guard under `apps/server/src/` (e.g. `App\Tenant\Boundary\*`), running after `TenantSubscriber` (kernel.request prio < 20). Builds on existing `App\Tenant\TenantContext`, `TenantResolver`, `TenantSubscriber`.
- Code: an execution-context declaration mechanism (attribute/interface) for `App\Command\*`, Messenger handlers, and scheduled jobs, plus a Messenger middleware / stamp that strips and re-establishes tenant context only from explicit metadata.
- Config: route boundary classification and (optionally) a Messenger middleware registration in `apps/server/config/`.
- Systems: enforces ADR-003 fail-closed route boundary and ADR-001 execution-context + async-boundary invariants. PostgreSQL-only; no broker/Redis required (Messenger transport may use Doctrine/sync).
- Out of scope: concrete tenant-facing URL topology, login flow, navigation, the authn/authz *mechanisms* themselves (separate changes), Back Office surface internals, and global/federated identity.
