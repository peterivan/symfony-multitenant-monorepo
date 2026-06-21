## 1. Boundary and dependency groundwork

- [ ] 1.1 Confirm upstream dependencies are available: `identity-model` (central platform-owned + tenant-owned identity records), `tenant-database-connectivity` (tenant identity-store selection from resolved tenant context), and `tenant-boundary-enforcement` (route classification + fail-closed tenant boundary).
- [ ] 1.2 Verify `TenantSubscriber` runs at kernel.request priority 20 (before the Symfony firewall at priority 8) so tenant context is resolved before tenant authentication, and confirm `App\Tenant\TenantContext` exposes the resolved tenant identifier and eligibility/lifecycle signal.
- [ ] 1.3 Confirm Back Office routes are classified as central (`/bo`) and tenant-facing routes are classified tenant-facing, per `tenant-boundary-enforcement`, so each can be mapped to its own firewall.

## 2. Platform authentication (central Back Office firewall)

- [ ] 2.1 Add a central platform user provider bound exclusively to the central platform-owned identity store (`App\Security\Platform\*`); it must never read a tenant identity store.
- [ ] 2.2 Configure a Symfony Security `platform` firewall in `apps/server/config/packages/security.yaml` matched to `/bo`, running in central context, using the platform user provider, and requiring no tenant resolution to enter.
- [ ] 2.3 Ensure tenant-resolution failure (missing/unresolved/ambiguous/unavailable) does not block or alter Back Office authentication.
- [ ] 2.4 Ensure failed platform authentication is final and never falls back to any tenant identity store or tenant authentication.

## 3. Tenant authentication (tenant-context-first tenant firewall)

- [ ] 3.1 Add a tenant user provider that resolves the tenant identity store from the resolved `TenantContext` (via `tenant-database-connectivity` selection); it must never use a default/stale tenant, the central store, or a shared tenant-user source (`App\Security\Tenant\*`).
- [ ] 3.2 Add a tenant authenticator/guard that fails closed before any tenant identity-store access when tenant context is missing, unresolved, inactive, suspended, archived, deleted, ambiguous, or unavailable.
- [ ] 3.3 Configure a Symfony Security `tenant` firewall in `security.yaml` matched to tenant-facing routes, ordered after `TenantSubscriber` (prio 20) and using the tenant user provider/authenticator.
- [ ] 3.4 Ensure failed tenant authentication is final and never falls back to the central platform-owned identity store or platform authentication.

## 4. Boundary-scoped authentication state and isolation

- [ ] 4.1 Configure isolated authentication state per boundary (separate firewall security contexts and session/cookie scope) so platform state and tenant state do not imply each other.
- [ ] 4.2 Bind tenant authentication state to the resolved tenant identifier for which the user authenticated, and reject it when the currently resolved tenant context does not match.
- [ ] 4.3 Re-check tenant lifecycle eligibility when using existing tenant authentication state, rejecting state whose bound tenant has become ineligible.
- [ ] 4.4 Ensure platform state grants only Back Office access (no tenant-user identity, no ordinary tenant-facing access) and tenant state grants only its one resolved tenant (no Back Office, no other tenant).

## 5. Validation and tests

- [ ] 5.1 Test that Back Office authentication uses the central platform-owned store and does not require tenant resolution.
- [ ] 5.2 Test tenant-context-first ordering: the tenant authenticator does not touch any tenant identity store before tenant resolution and eligibility.
- [ ] 5.3 Test tenant authentication uses the store selected by the resolved tenant context and not a default/stale tenant, central store, or shared source.
- [ ] 5.4 Test fail-closed for each bad-context case: missing, unresolved, inactive, suspended, archived, deleted, ambiguous, unavailable.
- [ ] 5.5 Test no silent fallback in either direction (platform→tenant and tenant→platform).
- [ ] 5.6 Test matching email/identity attributes across boundaries and across tenants do not imply authentication elsewhere.
- [ ] 5.7 Test tenant authentication state is bound to one tenant, rejected on tenant mismatch, rejected after the tenant becomes ineligible, and never grants another tenant or Back Office.
- [ ] 5.8 Run `docker compose exec app php bin/console lint:container` and the test suite to confirm both firewalls are wired without cross-boundary fallback.
