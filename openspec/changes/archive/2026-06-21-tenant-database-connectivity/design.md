## Context

ADR-001 mandates database-per-tenant with separate Doctrine connections, entity managers, and mapping boundaries for central and tenant data. Tenant resolution already exists: `App\Tenant\TenantResolver`, `App\EventSubscriber\TenantSubscriber` (kernel.request priority 20), and `App\Tenant\TenantContext` hold the resolved tenant. The `central-database-foundation` change provides the central Doctrine connection and a Tenant registry entity that stores tenant database-location metadata (not only the database name, per ADR-001).

What is still missing is the runtime step between "tenant is resolved" and "tenant data is queried": selecting and opening the correct tenant database connection and EntityManager from registry metadata, keyed by the active `TenantContext`. ADR-001 requires this selection to happen before tenant EntityManager or connection use, to fail closed when context is missing/unresolved/inactive, and to never fall back to the central database, a default tenant, or stale context. The stack is PostgreSQL 16 only (ADR-005); there is no Redis or broker.

## Goals / Non-Goals

**Goals:**

- Select and open the correct tenant database connection and EntityManager from `central-database-foundation` registry metadata, keyed by the active `TenantContext`.
- Perform that selection lazily but strictly before the first tenant EntityManager or connection use within an execution unit.
- Fail closed when tenant context is missing, unresolved, ambiguous, inactive, or when the registry lookup fails or yields incomplete location metadata.
- Keep central and tenant Doctrine access distinguishable in configuration and code.
- Release tenant Doctrine resources when the execution unit ends, including failure paths.
- Require shared caches, sessions, and storage holding tenant data to be tenant-namespaced.

**Non-Goals:**

- Tenant resolution and `TenantContext` lifecycle ownership (owned by existing tenancy layer / ADR-001; reused here).
- Tenant provisioning, registry CRUD, and central connection setup (owned by `central-database-foundation`).
- Tenant migrations and migration orchestration (owned by `tenant-migrations`).
- Connection pooling, multi-region placement, sharding, and cross-tenant query optimization.
- Async tenant context propagation rules beyond stripping/re-establishing context (owned by the tenancy layer; ADR-001).

## Decisions

- Introduce a tenancy-layer tenant connection provider/factory that, given the active `TenantContext`, looks up the tenant in the registry, reads its database-location metadata, and produces a tenant Doctrine connection plus EntityManager bound to that tenant. The tenancy layer is the only path to a tenant connection (ADR-001: tenant access mediated by tenancy infrastructure).
- Routing is keyed strictly by the resolved tenant identifier in `TenantContext`. If no active/resolved tenant is present at the moment a tenant connection is requested, the provider throws and the request fails closed; it never returns a central or default connection.
- Selection is lazy (resolved on first tenant connection request within the execution unit) but guaranteed to occur before any tenant EntityManager/connection use, because the only way to obtain a tenant EntityManager/connection is through the provider.
- Central vs tenant access is kept distinguishable via named Doctrine connections/managers (a dedicated tenant connection name and tenant EntityManager, separate from the central ones from `central-database-foundation`) and separate mapping boundaries; code requests the tenant manager explicitly through the tenancy layer rather than the default manager.
- A tenant database belongs to exactly one tenant: routing maps one resolved tenant identifier to one tenant database from registry metadata; the provider never multiplexes one connection across tenants and re-resolves per tenant identifier.
- Registry lookup failures, missing tenants, inactive lifecycle state, and incomplete location metadata are all treated as fail-closed errors, not as "use a default".
- Tenant Doctrine resources are closed/disposed at the end of the execution unit (kernel.terminate / command teardown / per-iteration in cross-tenant runs), including on exceptions, so no tenant connection leaks across units.
- Shared caches, sessions, and storage that hold tenant-scoped data must be tenant-namespaced (key/namespace derived from the tenant identifier) so routing does not leak tenant data through shared infrastructure.

## Risks / Trade-offs

- Lazy resolution risk: code might try to use a tenant manager before context is set. Mitigation: the only access path is the provider, which fails closed when no resolved context exists, surfacing the mistake immediately rather than silently using central.
- Stale metadata risk: ADR-001 allows runtime metadata change and requires stale handling. This change reads current registry metadata per resolution; if a runtime metadata cache is later added, cache invalidation must be added with it (flagged, not implemented here).
- Resource lifecycle risk: failing to dispose tenant connections leaks across requests/iterations. Mitigation: explicit teardown on terminate and per-iteration disposal in cross-tenant execution, including failure paths.
- Performance trade-off: opening a tenant connection per execution unit adds latency versus a shared pool; accepted to preserve strict isolation and fail-closed behavior on PostgreSQL-only infrastructure.
- Shared-infrastructure leakage risk: caches/sessions/storage without tenant namespacing could leak tenant data. Mitigation: require tenant-namespacing for any shared store carrying tenant data, validated in review and tests.
