## Why

Tenant-scoped requests need the correct tenant database opened from registry metadata, keyed by the resolved tenant context, before any tenant EntityManager or connection is used. Without runtime routing, tenant-scoped code has no isolated, fail-closed path to its own database (ADR-001).

## What Changes

- Add a tenant connection/EntityManager routing layer that selects and opens the correct tenant database from `central-database-foundation` registry metadata, keyed by the active `TenantContext`.
- Resolve and open the tenant database lazily, before first tenant EntityManager or connection use, with no fallback to the central database, a default tenant, or stale context.
- **BREAKING** Tenant-scoped Doctrine access is only available through the routing layer; direct use of a tenant connection/EntityManager without an active, resolved tenant context fails closed.
- Keep central and tenant Doctrine access distinguishable in Doctrine configuration and in code (named connections/managers, separate mapping boundaries).
- Release tenant Doctrine resources (connection/EntityManager) when the execution unit ends, including failure paths.
- Require shared caches, sessions, and storage that hold tenant-scoped data to be tenant-namespaced.

## Capabilities

### New Capabilities

- `tenant-database-routing`: Runtime selection and opening of the correct tenant database connection and EntityManager from tenant registry metadata, keyed by the active tenant context, failing closed on any ambiguity or lookup failure.

### Modified Capabilities

## Impact

- New tenancy-layer routing code under `App\Tenant` (e.g. tenant connection factory / EntityManager provider) integrating with `App\Tenant\TenantContext`.
- Doctrine configuration: named tenant connection and tenant EntityManager kept distinct from the central connection introduced by `central-database-foundation`.
- Depends on `central-database-foundation` (central connection + Tenant registry entity exposing tenant database-location metadata).
- Consumes `App\EventSubscriber\TenantSubscriber` output (resolved tenant slug in `TenantContext`); no change to resolution itself.
- Affects any code that opens or holds tenant connections/EntityManagers, plus shared cache/session/storage configuration that may carry tenant data.
