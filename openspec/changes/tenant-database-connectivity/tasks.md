## 1. Doctrine configuration boundaries

- [ ] 1.1 Confirm the central connection/EntityManager from `central-database-foundation` and its named identity.
- [ ] 1.2 Define a distinct named tenant Doctrine connection and tenant EntityManager, separate from the central ones.
- [ ] 1.3 Establish separate mapping boundaries so central entities and tenant entities map only to their own managers.

## 2. Registry metadata access

- [ ] 2.1 Add a read path from the active tenant identifier to the Tenant registry entry via the central connection.
- [ ] 2.2 Expose the tenant database-location metadata (full connection target, not only database name) from the registry entry.
- [ ] 2.3 Define error types for tenant-not-found, registry-lookup-failure, and incomplete-location-metadata.

## 3. Tenant connection routing layer

- [ ] 3.1 Implement a tenancy-layer tenant connection provider/factory keyed by `App\Tenant\TenantContext`.
- [ ] 3.2 Open the tenant connection and EntityManager lazily on first request, before any tenant query.
- [ ] 3.3 Fail closed when context is missing, unresolved, ambiguous, or inactive, with no central/default/stale fallback.
- [ ] 3.4 Fail closed on registry lookup failure and incomplete location metadata.
- [ ] 3.5 Bind one tenant identifier to exactly one tenant database and re-resolve when the active tenant identifier changes.
- [ ] 3.6 Ensure tenant Doctrine access is reachable only through the routing layer.

## 4. Resource lifecycle

- [ ] 4.1 Release tenant connection/EntityManager at end of HTTP request (kernel.terminate) and command teardown.
- [ ] 4.2 Release tenant resources on failure paths (exceptions) without leaking across execution units.
- [ ] 4.3 Dispose prior tenant resources and context per iteration in cross-tenant execution before opening the next.

## 5. Shared infrastructure namespacing

- [ ] 5.1 Tenant-namespace shared caches that hold tenant-scoped data by the active tenant identifier.
- [ ] 5.2 Tenant-namespace or per-tenant route shared sessions and storage that hold tenant-scoped data.

## 6. Validation

- [ ] 6.1 Tests for opening the correct tenant database from registry metadata for a resolved tenant.
- [ ] 6.2 Tests for fail-closed behavior on missing/unresolved/ambiguous/inactive context (no central/default/stale fallback).
- [ ] 6.3 Tests for fail-closed behavior on registry lookup failure, tenant-not-found, and incomplete metadata.
- [ ] 6.4 Tests for central-vs-tenant Doctrine access separation and one-tenant-per-database mapping.
- [ ] 6.5 Tests for tenant resource cleanup after success, after failure, and per cross-tenant iteration.
- [ ] 6.6 Tests for tenant-namespacing of shared caches/sessions/storage holding tenant data.
