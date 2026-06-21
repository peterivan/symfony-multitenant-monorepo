## Why

The platform needs an explicit data/domain model for who can exist where, before any login flow is built. ADR-006 mandates two separate identity boundaries: platform operators are central platform identities living in central storage with Back Office access, and tenant users are tenant-owned identities living in tenant storage, scoped to the selected tenant. Without modeling these as two distinct, non-merging identity boundaries, code would drift toward a central-identity-plus-membership model, treat a shared email as proof of one person, or let an operator identity imply tenant access — all forbidden by ADR-006. This change establishes the identity data model only; authentication (ADR-007) is a separate change.

## What Changes

- Define `platform-operator-identity` as a central-owned identity domain model stored in central storage (per `central-database-foundation`), scoped to the platform/Back Office boundary (ADR-002) and never to a tenant.
- Define `tenant-user-identity` as a tenant-owned identity domain model stored in tenant storage (per `tenant-database-connectivity`), whose existence and access are scoped to the selected tenant context only.
- Establish the separation invariants as model-level rules: the same email/identity attribute may exist independently in multiple tenants and across boundaries without implying shared identity, cross-tenant access, or a platform-level "same person".
- Forbid, at the model level, a default central tenant-membership model and any merging of platform-operator and tenant-user identities by matching identity attributes.
- Make name/email/profile changes and account recovery for tenant users tenant-local concerns owned by the tenant boundary.
- Require audit attribution to distinguish platform-operator identity, tenant-user identity, selected tenant context, and platform operation scope when those facts apply.

## Capabilities

### New Capabilities

- `platform-operator-identity`: Central-owned platform-operator identity model living in central storage, scoped to the Back Office boundary and never implying tenant-user identity or tenant access.
- `tenant-user-identity`: Tenant-owned tenant-user identity model living in tenant storage, scoped to the selected tenant, with tenant-local profile/recovery and no cross-tenant or platform-level identity continuity.

### Modified Capabilities

## Impact

- New central-owned platform-operator identity entity under the central mapping boundary (`App\Central\...`), using the `central` connection/EntityManager from `central-database-foundation`.
- New tenant-owned tenant-user identity entity under the tenant mapping boundary, using the tenant connection/EntityManager from `tenant-database-connectivity`, only reachable inside a resolved tenant context.
- Audit attribution shape must carry identity-boundary, identity reference, selected tenant context, and platform-operation scope fields.
- Depends on `central-database-foundation` (central storage + Doctrine) and `tenant-database-connectivity` (tenant DB selection / resolved tenant context).
- Constrains later authentication and authorization work (ADR-007) to operate within these two separate boundaries; introduces no login, session, credential, role, or permission behavior.
