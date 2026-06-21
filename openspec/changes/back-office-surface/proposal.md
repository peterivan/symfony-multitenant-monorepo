## Why

The platform needs a dedicated operational surface for platform operators that is structurally separated from tenant-facing application areas. ADR-002 mandates a Back Office exposed at runtime under `/bo` that runs in central context, is not reachable as tenant-subdomain routes, never derives access from tenant-local identity, and audits tenant-impacting operations. Without an explicit boundary, platform administration would couple to tenant-local authorization and risk accidental privilege expansion and ambiguous tenant-context operations.

This change specs the Back Office boundary and the mandatory audit coverage for platform operations. It does not define the platform-operator identity/authentication mechanism itself (owned elsewhere); it defines where Back Office lives, how its tenant-context and authorization boundaries behave, and what must be audited.

## What Changes

- Establish `/bo` as a central-context-only operational surface that starts without active tenant context.
- Forbid Back Office routes from being registered or reachable as tenant-subdomain or tenant-facing routes.
- Require Back Office actions to use platform-operator authorization only; tenant-local users, memberships, roles, and permissions must never grant Back Office access.
- Prevent Back Office from implicitly inheriting tenant context from tenant-facing requests, sessions, workers, or navigation state.
- Allow tenant context to be established only from explicit platform-operator intent, scoped to one bounded operation, and cleared when that operation ends (including failures).
- Require any Back Office capability that accesses, mutates, exports, or deletes tenant-owned data to make tenant selection and data scope explicit and fail closed when scope is missing or ambiguous.
- Mandate audit coverage for tenant lifecycle changes, cross-tenant operations, and tenant-owned data access or mutation.

## Capabilities

### New Capabilities

- `back-office-boundary`: Defines the `/bo` runtime surface, its central-context-only routing, platform-operator authorization boundary, and scoped tenant-context lifecycle for platform operations.
- `platform-operation-audit`: Defines mandatory, non-optional audit coverage for tenant lifecycle changes, cross-tenant operations, and tenant-owned data access or mutation performed through Back Office.

### Modified Capabilities

## Impact

- Affected ADRs: ADR-002 (Back Office), ADR-001 (Tenancy Architecture) for the data-ownership and central-context model.
- Affected systems: runtime routing configuration for the `/bo` namespace; authorization layer (platform-operator boundary distinct from tenant-local authorization); tenant-context lifecycle (`App\Tenant\*`, `TenantSubscriber`) must not initialize tenant context for Back Office requests.
- No application code, ADRs, or other change directories are modified by this planning artifact.
