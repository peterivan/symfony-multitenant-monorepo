## Context

ADR-002 establishes a dedicated Back Office operational surface under `/bo` for platform-level operations. It is internal, for platform operators, and explicitly not tenant-facing. Tenant resolution already exists in `App\Tenant\*` with a `TenantSubscriber` running at `kernel.request` priority 20 that resolves tenant context from the request host. Back Office must not participate in that host-based resolution: it starts in central context (no active tenant) and must not implicitly inherit tenant context from tenant-facing requests, sessions, or workers.

ADR-001 establishes database-per-tenant with central-owned metadata, so platform operations split between central-context operations (tenant registry) and bounded tenant-context operations (tenant database initialize/migrate/seed/inspect/maintain). Authorization for Back Office is the platform-operator boundary, kept separate from tenant-local users, roles, memberships, and permissions.

This change specs the boundary and the mandatory audit requirements only. The concrete platform-operator identity/authentication mechanism, audit record structure, audit storage layers, UI component architecture, and frontend build system are out of scope per ADR-002.

## Goals / Non-Goals

**Goals:**
- Spec `/bo` as a central-context-only runtime surface that starts without active tenant context and is not reachable via tenant subdomains.
- Spec the platform-operator authorization boundary so tenant-local identity can never grant Back Office access.
- Spec scoped, explicit, and cleared tenant-context lifecycle for tenant lifecycle and tenant-owned-data operations.
- Spec fail-closed behavior when tenant scope is missing or ambiguous.
- Spec mandatory, non-optional audit coverage for tenant lifecycle changes, cross-tenant operations, and tenant-owned data access or mutation.

**Non-Goals:**
- Defining the platform-operator identity model or authentication mechanism (owned by the Identity boundary).
- Defining audit record structure, audit layers, or audit storage implementation.
- Defining tenant-facing application behavior, tenant-local authorization, or support-workflow details.
- Defining concrete UI components, navigation design, or the frontend build/deployment mechanics.

## Decisions

- The boundary is defined by runtime route exposure, authorization, tenant-context behavior, data access rules, and audit requirements — not by filesystem placement or frontend technology. Back Office may be implemented as a separate application as long as these invariants hold.
- Back Office routes live under the `/bo` namespace and are registered only in central context. They must not be registered as tenant-subdomain routes, and route registration/inspection must be able to verify this.
- The `TenantSubscriber` host-based tenant resolution must not apply to `/bo` requests; Back Office entry must not require tenant resolution and must not initialize tenant context from the request host.
- Authorization for any Back Office action is the platform-operator boundary. Tenant-local users, memberships, roles, and permissions are never consulted to grant Back Office access. Tenant-local identities never auto-inherit platform-operator privileges.
- Tenant context for a Back Office operation may only be established from explicit platform-operator intent (an explicit operation selecting a specific tenant), is scoped strictly to that bounded operation, and is cleared when the operation ends, including on failure.
- Tenant registry operations run in central context. Tenant database initialize/migrate/seed/inspect/maintain operations may establish bounded tenant context.
- Any access/derivation/export/mutation/deletion of tenant-owned data must make the selected tenant and data scope explicit and fail closed if scope is missing or ambiguous.
- Audit coverage for tenant lifecycle changes, cross-tenant operations, and tenant-owned data access or mutation is mandatory and must not be deferrable, skippable, or optional.

## Risks / Trade-offs

- Risk: a shared kernel subscriber accidentally resolves tenant context for `/bo` requests. Mitigation: explicit exclusion of the `/bo` namespace from host-based tenant resolution plus route/boundary tests verifying central-context entry.
- Risk: convenience pressure to reuse tenant-local roles for support access. Mitigation: hard invariant and authorization tests that tenant-local identity cannot grant Back Office access.
- Risk: tenant context leaking beyond a bounded operation (especially on failure paths) into later work in the same execution unit. Mitigation: scoped establishment with mandatory clearing on success and failure, verified by tests.
- Risk: ambiguous or implicit tenant context causing cross-tenant data exposure. Mitigation: explicit tenant/scope selection and fail-closed defaults.
- Risk: audit being skipped under time pressure or error paths. Mitigation: making audit non-optional for the listed operation classes and asserting audit emission in tests.
- Trade-off: maintaining a separate operational surface and authorization model costs more than embedding admin in tenant areas, but ADR-002 accepts this because accidental privilege expansion is more costly.
