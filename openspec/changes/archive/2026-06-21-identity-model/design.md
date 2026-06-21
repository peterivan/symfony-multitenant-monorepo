## Context

ADR-006 (Identity Boundary Model) requires two separate identity boundaries. Platform operators are platform-owned identities that live in central storage and access Back Office (ADR-002). Tenant users are tenant-owned identities that live in tenant storage and access tenant-facing surfaces (ADR-003); their existence, profile state, and access are scoped to the selected tenant. ADR-006 explicitly rejects a default central-identity-plus-tenant-membership model, rejects any platform-level "same person" concept, and states that matching identity attributes (such as a shared email) across tenants or boundaries must never be treated as proof of shared identity or cross-tenant access.

The foundations this change builds on already exist: `central-database-foundation` provides the named `central` connection/EntityManager and central mapping boundary, and `tenant-database-connectivity` provides runtime selection of the correct tenant database keyed by the active `TenantContext`. This change defines only the identity data/domain model and its separation invariants. It does not define authentication, sessions, credentials, MFA, password reset flows, roles, permissions, or policy engines — those belong to ADR-007 and later authorization work.

## Goals / Non-Goals

**Goals:**
- Model platform-operator identity as a central-owned entity in central storage, scoped to the platform/Back Office boundary.
- Model tenant-user identity as a tenant-owned entity in tenant storage, scoped to the selected tenant.
- Encode the ADR-006 separation invariants as model-level rules: independence of the same email across tenants and boundaries, no central membership model, no merging by matching attributes, no platform-level "same person".
- Keep tenant-user name/email/profile changes and account recovery tenant-local.
- Define audit attribution that distinguishes operator identity, tenant-user identity, selected tenant context, and platform operation scope.

**Non-Goals:**
- Authentication, sessions, credentials, MFA, password reset behavior, invitation flows (ADR-007).
- Authorization: roles, permissions, policy engines, Back Office access decisions.
- A central identity plus tenant membership model, identity federation, impersonation, delegated administration, service accounts, or directory synchronization (ADR-006 leaves these to future ADRs).
- Cross-tenant identity continuity or central account/profile management for tenant users.
- Any non-PostgreSQL infrastructure.

## Decisions

- **Two physically separate identity stores, one per boundary.** Platform-operator identities are mapped only to the central EntityManager and central storage; tenant-user identities are mapped only to the tenant EntityManager and tenant storage. There is no shared identity table and no foreign key crossing the boundary. This makes the ADR-006 ownership invariant structural rather than convention, and prevents central code from reaching tenant identities or vice-versa.
- **Independent per-tenant uniqueness, no global uniqueness.** Identity attributes such as email are unique only within their own store: unique within central platform-operator storage, and unique within a single tenant's storage. The same email may exist independently in many tenants and may also exist as a platform operator. The model deliberately defines no cross-store or global uniqueness constraint, because such a constraint would imply a platform-level "same person" that ADR-006 forbids.
- **No linking identifier across boundaries.** The model introduces no shared person/account identifier, no cross-tenant user id, and no central membership/assignment record granting tenant access. A tenant-user identity references only its own tenant context; a platform-operator identity references only the platform boundary. Matching attributes are data coincidences, never identity links. Alternative considered: a central "person" record fanned out to tenant memberships — rejected as the central-identity-plus-membership model ADR-006 rejects by default.
- **Tenant-user identity is scoped to the selected tenant.** A tenant-user identity is only meaningful and only reachable inside a resolved tenant context (via `tenant-database-connectivity`). Existence in one tenant carries no existence, access, or continuity in another tenant. Tenant-local membership/assignment records (ADR-006 allows these inside a tenant) remain a tenant concern and are out of scope here.
- **Tenant-local lifecycle for tenant users.** Name, email, profile, and account-recovery state for tenant users are stored and changed entirely within the tenant boundary. No central record mirrors or coordinates these. This keeps ADR-006's tenant-local-lifecycle invariant explicit and avoids cross-tenant profile coupling.
- **Operator identity does not model tenant access.** The platform-operator identity model carries platform/Back Office attribution only. It deliberately contains no tenant-user fields and no tenant-access fields; any tenant-operational reach by operators is a future platform operation governed by ADR-002, not an attribute of the identity.
- **Audit attribution carries boundary-distinguishing facts.** The audit attribution shape records the identity boundary (platform-operator vs tenant-user), the identity reference within that boundary, the selected tenant context when one applies, and the platform-operation scope when the action is a platform operation. This lets audit records distinguish the four facts ADR-006 requires without conflating an operator with a tenant user who shares attributes.
- **PostgreSQL-native modeling.** Use `uuid` primary keys and `timestamptz` audit columns per ADR-005; per-store unique constraints on email; no portability shims.

## Risks / Trade-offs

- [Tenant-local identities make future central account management, global profile, cross-tenant recovery, and federation more expensive] → Accepted per ADR-006, which optimizes for tenant isolation; a superseding ADR is required to change the model.
- [Shared email across tenants could be misread as one person] → Model defines no cross-store uniqueness and no linking id; tests must assert that same-email identities in different tenants are independent and grant no cross-tenant access.
- [An operator identity could be mistaken for a tenant user, or vice-versa, granting wrong access] → Separate stores, separate mapping boundaries, no cross-boundary id; tests must assert operator identity grants no tenant access and tenant-user identity grants no Back Office access.
- [Code could drift toward a central membership model] → No central membership/assignment entity is defined; code review (ADR-006 validation) must reject any such addition without a superseding ADR.
- [Audit records could fail to distinguish boundaries] → Attribution shape mandates boundary + identity reference + tenant context + platform-operation scope fields so the four facts are separable.
