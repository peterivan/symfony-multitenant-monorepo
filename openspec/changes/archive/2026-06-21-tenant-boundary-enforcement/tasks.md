## 1. Tenant-Facing Route Boundary Classification

- [x] 1.1 Define an explicit tenant-facing route boundary marker (route default / attribute / controller marker) under `apps/server/src/` (e.g. `App\Tenant\Boundary\`).
- [x] 1.2 Ensure route classification resolves every route to exactly one of tenant-facing, central platform, or Back Office, with no overlap.
- [x] 1.3 Add route-inspection tests asserting tenant-facing routes carry the marker and are absent from Back Office / central platform route sets.

## 2. Fail-Closed Enforcement Gate

- [x] 2.1 Add a kernel.request enforcement listener that runs after `TenantSubscriber` (prio < 20) and before the security firewall.
- [x] 2.2 Gate tenant-facing routes on resolved, active, unambiguous tenant context before tenant-local authn/authz/data access.
- [x] 2.3 Implement a uniform non-tenant-revealing fail-closed response for missing, unresolved, inactive, ambiguous, and unestablishable context.
- [x] 2.4 Ensure tenant-scoped access never falls back to the central database or a default tenant on enforcement failure.

## 3. Tenant-Local Scoping Guarantees

- [x] 3.1 Ensure tenant-local identity, membership, role, and permission lookups reached via tenant-facing routes resolve only against the active tenant.
- [x] 3.2 Ensure tenant-local roles/permissions grant no Back Office or platform-level capabilities and platform-operator privilege is not inherited from tenant-local state.

## 4. Enforcement Tests

- [x] 4.1 Test enforcement ordering: runs after resolution, before authentication; authn/authz do not execute without context.
- [x] 4.2 Test fail-closed for missing, unresolved, inactive, ambiguous, and unestablishable tenant context (no tenant-owned data exposed).
- [x] 4.3 Test tenant-local checks are scoped to the active tenant and participation in one tenant grants nothing in another.
- [x] 4.4 Test no cross-tenant leakage via implicit shared state across consecutive requests with different tenants.

## 5. Execution Context Declaration

- [x] 5.1 Define an execution-context declaration mechanism (attribute/interface) for commands, workers, and jobs: central / single-tenant / cross-tenant.
- [x] 5.2 Apply declarations to existing `App\Command\*`, Messenger handlers, and scheduled jobs.
- [x] 5.3 Fail closed for undeclared execution units; require explicit tenant selection for single-tenant and explicit iteration for cross-tenant.

## 6. Async Boundary Context Handling

- [x] 6.1 Add a Messenger middleware / stamp that strips active tenant context on dispatch.
- [x] 6.2 Re-establish tenant context on the consumer only from explicit routing metadata / job attributes / command options.
- [x] 6.3 Fail closed for tenant-scoped messages lacking resolvable explicit tenant metadata.
- [x] 6.4 Establish and dispose tenant context per tenant iteration for cross-tenant jobs, including failure paths; clear context after failures with no fallback to default/stale tenant.

## 7. Execution Context Tests

- [x] 7.1 Test no implicit tenant-context inheritance across dispatch/consume boundaries.
- [x] 7.2 Test tenant re-establishment from explicit metadata and fail-closed on missing metadata.
- [x] 7.3 Test per-execution-unit context establish/dispose including failure paths and no shared-state leakage between units.
- [x] 7.4 Test undeclared / context-mismatched execution units fail closed.

## 8. Validation

- [x] 8.1 Run `docker compose exec app` test suite and static analysis for the new boundary and declaration components.
- [x] 8.2 Run `npx --yes @fission-ai/openspec@latest validate tenant-boundary-enforcement --json` and confirm it passes.
