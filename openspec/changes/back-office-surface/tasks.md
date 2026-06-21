## 1. Runtime Surface And Routing

- [ ] 1.1 Define the `/bo` route namespace and register Back Office routes as central-context-only routes.
- [ ] 1.2 Exclude the `/bo` namespace from host-based tenant resolution so Back Office entry does not require tenant resolution.
- [ ] 1.3 Ensure Back Office requests start in central context without an active tenant context and do not initialize tenant context from the request host.
- [ ] 1.4 Add route registration/inspection tests verifying `/bo` exposure and that no Back Office route is registered as a tenant-subdomain route.

## 2. Authorization Boundary

- [ ] 2.1 Route all Back Office actions through platform-operator authorization only.
- [ ] 2.2 Ensure tenant-local users, memberships, roles, and permissions cannot grant Back Office access.
- [ ] 2.3 Ensure tenant-local identities cannot auto-inherit platform-operator privileges.
- [ ] 2.4 Add authorization tests for platform-operator access granted and tenant-local access denied.

## 3. Tenant-Context Lifecycle

- [ ] 3.1 Establish tenant context for Back Office operations only from explicit platform-operator intent, scoped to the bounded operation.
- [ ] 3.2 Clear scoped tenant context when the operation ends, including on failure paths.
- [ ] 3.3 Keep tenant registry operations in central context.
- [ ] 3.4 Prevent implicit inheritance of tenant context from tenant-facing requests, sessions, workers, or navigation state.
- [ ] 3.5 Require explicit tenant and data scope for tenant-owned data access/mutation and fail closed when scope is missing or ambiguous.
- [ ] 3.6 Add tests for scoped establishment, clearing on success and failure, no implicit inheritance, and fail-closed behavior.

## 4. Mandatory Audit

- [ ] 4.1 Record audit entries for tenant lifecycle changes performed through Back Office.
- [ ] 4.2 Record audit entries for cross-tenant operations.
- [ ] 4.3 Record audit entries for tenant-owned data access and mutation.
- [ ] 4.4 Make audit recording non-optional for these operation classes (no defer, skip, or opt-out).
- [ ] 4.5 Add audit assertion tests for lifecycle, cross-tenant, and tenant-owned data operations.

## 5. Validation

- [ ] 5.1 Run boundary, authorization, tenant-context, and audit tests.
- [ ] 5.2 Code review against ADR-002 invariants (no tenant business workflows, no implicit tenant context, no tenant-local authorization bypass, audit coverage present).
