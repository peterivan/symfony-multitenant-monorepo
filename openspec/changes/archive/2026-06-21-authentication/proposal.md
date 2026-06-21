## Why

ADR-007 mandates two separate authentication boundaries with no shared boundary by default. Platform operators authenticate against platform-owned identities in central storage for Back Office access, and Back Office entry must not require tenant resolution. Tenant users authenticate against tenant-owned identities in the tenant identity store selected by the resolved tenant context, and tenant resolution must complete before tenant-user authentication is evaluated. Today `TenantSubscriber` (kernel.request prio 20) resolves tenant context ahead of the firewall (prio 8), and the upstream changes deliver tenant-scoped identities (`identity-model`), tenant identity-store selection (`tenant-database-connectivity`), and the fail-closed tenant route boundary (`tenant-boundary-enforcement`). But there is no authentication wiring: no separate firewalls per boundary, no tenant-context-first tenant authenticator bound to the resolved tenant store, no fail-closed behavior on bad tenant context, and no isolation between platform and tenant authentication state. Without this, authentication could silently fall back between stores, cross boundaries on matching email, or leak a session across tenants — violating ADR-007, ADR-006, ADR-003, and ADR-002.

## What Changes

- Add a central platform-authentication firewall for Back Office (`/bo`) that authenticates platform operators against the central platform-owned identity store and requires no tenant resolution for entry.
- Add a tenant-authentication firewall for tenant-facing routes that authenticates tenant users only after tenant resolution, against the tenant identity store selected by the resolved tenant context.
- Make tenant authentication tenant-context-first: it must not inspect tenant-local identities, credentials, sessions, or authentication state until tenant context is resolved and the tenant is eligible for tenant-facing access.
- Fail closed for tenant authentication when tenant context is missing, unresolved, inactive, suspended, archived, deleted, ambiguous, unavailable, or otherwise cannot be established — without inspecting any tenant identity store.
- Forbid silent fallback between central and tenant identity stores in either direction: failed platform authentication must not attempt tenant authentication and vice versa; neither firewall may authenticate against the other boundary's store.
- Scope and isolate authentication state per boundary: platform state is central Back Office state; tenant state is bound to one resolved tenant. Reject tenant authentication state when the current resolved tenant context does not match, when the tenant becomes ineligible, and never let it grant another tenant or Back Office. Platform state must not grant tenant-user identity or ordinary tenant-facing access.
- Ensure matching email or other identity attributes across boundaries or across tenants never imply authentication elsewhere.

## Capabilities

### New Capabilities
- `platform-authentication`: Central-context authentication of platform operators against the central platform-owned identity store via a Back Office firewall, requiring no tenant resolution for entry, never falling back to or against tenant identity stores, with central authentication state isolated from tenant state and unable to grant tenant-user identity or tenant-facing access. (→ specs/platform-authentication/spec.md)
- `tenant-authentication`: Tenant-context-first authentication of tenant users via a tenant firewall, against the tenant identity store selected by the resolved tenant context, failing closed on missing/unresolved/inactive/suspended/archived/deleted/ambiguous/unavailable context, never falling back to or against the central store, with tenant authentication state bound to one resolved tenant, rejected on tenant mismatch or ineligibility, and unable to grant another tenant or Back Office access. (→ specs/tenant-authentication/spec.md)

### Modified Capabilities

## Impact

- Code: separate Symfony Security firewalls and authenticators under `apps/server/`, e.g. `App\Security\Platform\*` (central Back Office) and `App\Security\Tenant\*` (tenant-facing). Tenant authenticator/user provider resolves the identity store through the resolved tenant context (`App\Tenant\TenantContext`) and the tenant identity-store selection from `tenant-database-connectivity`; platform provider uses the central platform-owned identity store from `identity-model`.
- Code: a tenant-context-first guard ensuring the tenant authenticator does not execute or touch the tenant identity store before tenant resolution and eligibility, consistent with `tenant-boundary-enforcement`; a tenant session/state binding that records the resolved tenant and is rejected on mismatch or tenant ineligibility.
- Config: `apps/server/config/packages/security.yaml` firewalls mapped by boundary — Back Office (`/bo`, central, no tenant resolution) and tenant-facing routes (after `TenantSubscriber` prio 20, before/at the firewall prio 8) — with separate user providers, separate session/state, and no cross-boundary fallback.
- Systems: enforces ADR-007 boundary separation, tenant-context-first ordering, fail-closed, no silent cross-store fallback, and boundary-scoped/isolated authentication state. PostgreSQL-only; no extra infrastructure.
- Out of scope: roles/permissions/authorization policies (RBAC/ABAC), MFA, password complexity/reset workflows, OIDC/SAML/LDAP/federation, login-page UX, session storage implementation details, support impersonation, service accounts, and tenant directory synchronization (ADR-007 defers these).
