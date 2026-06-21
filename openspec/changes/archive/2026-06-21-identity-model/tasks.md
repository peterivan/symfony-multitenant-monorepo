## 1. Confirm boundaries and foundations

- [x] 1.1 Re-read ADR-006 invariants and confirm scope is identity data/domain model only (no authentication, authorization, roles, or sessions).
- [x] 1.2 Confirm `central-database-foundation` exposes the named `central` connection/EntityManager and central mapping boundary.
- [x] 1.3 Confirm `tenant-database-connectivity` exposes the tenant connection/EntityManager keyed by the resolved tenant context.

## 2. Platform-operator identity model (central storage)

- [x] 2.1 Define the central-owned platform-operator identity entity under the central mapping boundary, mapped only to the `central` EntityManager.
- [x] 2.2 Model platform/Back Office attribution only; include no tenant-user or tenant-access fields.
- [x] 2.3 Enforce email uniqueness only within central platform-operator storage; add no cross-store or global uniqueness.
- [x] 2.4 Add a central migration creating the platform-operator identity table.

## 3. Tenant-user identity model (tenant storage)

- [x] 3.1 Define the tenant-owned tenant-user identity entity under the tenant mapping boundary, mapped only to the tenant EntityManager and reachable only within a resolved tenant context.
- [x] 3.2 Model tenant-facing attribution and tenant-local name/email/profile/account-recovery state; include no Back Office or platform-operator fields.
- [x] 3.3 Enforce email/attribute uniqueness only within a single tenant store; add no cross-tenant or global uniqueness and no cross-boundary linking identifier.
- [x] 3.4 Add a tenant migration creating the tenant-user identity table.

## 4. Separation invariants

- [x] 4.1 Ensure no shared identity table, no cross-boundary foreign key, and no shared person/account identifier exist between the two models.
- [x] 4.2 Ensure no central tenant-membership or assignment record granting tenant access is introduced.
- [x] 4.3 Ensure matching identity attributes are never modeled as a link, merge, or "same person" across tenants or across boundaries.

## 5. Audit attribution

- [x] 5.1 Define the audit attribution shape carrying identity boundary, identity reference, selected tenant context, and platform-operation scope.
- [x] 5.2 Ensure attribution distinguishes platform-operator identity + platform operation from tenant-user identity + selected tenant context.

## 6. Verification

- [x] 6.1 Add tests that a tenant user in tenant A with the same email as a tenant user in tenant B gains no access or presence in tenant B.
- [x] 6.2 Add tests that a platform-operator identity grants no tenant-user identity, tenant-local authorization, or tenant-facing access.
- [x] 6.3 Add tests that tenant-user identity grants no Back Office access.
- [x] 6.4 Add data-model review confirming each identity remains owned by its central or tenant boundary and that no central membership model was introduced.
