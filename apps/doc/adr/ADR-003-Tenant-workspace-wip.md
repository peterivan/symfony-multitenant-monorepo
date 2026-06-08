# ADR-003: Tenant Workspace

## Status
Accepted  
Date: 2026-05-21

## Context
Proxia.Plan is a multi-tenant platform serving independent organizations operating within isolated
workspaces.

ADR-001 defines the tenancy architecture and ADR-002 defines the Back Office boundary. This ADR
defines the tenant workspace as the unit of ownership, membership, and tenant-scoped
authorization.

Without a clear workspace boundary, application responsibilities, membership checks, and tenant
operations become ambiguous and the risk of accidental cross-tenant access increases.

## Decision
Tenant workspaces are isolated ownership and authorization boundaries.

Each tenant workspace owns its tenant-local:
- users
- roles and permissions
- operational data
- business workflows
- configuration and preferences
- documents and records
- tenant-local integrations

Tenant-facing authentication must always resolve an explicit tenant context before it can identify
or authorize a user.

Tenant workspaces live under the `/app` route namespace.

Tenant-local identities, roles, and permissions apply only within the owning tenant workspace.
They must not grant platform-level operational capabilities.

Tenant-local identities are workspace-scoped identities, not global platform identities. The same
email address or login name may exist in multiple tenant workspaces as separate records.

Tenant membership is workspace-scoped. A person participating in multiple tenants must have a
separate membership in each tenant workspace.

Tenant workspaces remain independent from Back Office operational boundaries.

Back Office operates at platform scope and must not implicitly inherit tenant-local authorization,
tenant membership, or workspace context.

Cross-tenant access, inspection, reporting, synchronization, imports, exports, and operational
actions must remain explicit and, where applicable, auditable. They must not occur through implicit
shared state.

The platform does not initially support shared cross-tenant workspaces, federated tenant identity,
or organization-spanning authorization models.

Tenant-owned operational data lives in tenant databases. Platform metadata, operator accounts, and
other central infrastructure data remain outside tenant databases.

Features are tenant-scoped by default unless explicitly defined as platform-scoped.

## Consequences

### Positive
- strong alignment between workspace ownership and tenant isolation
- simpler authorization reasoning
- reduced risk of accidental cross-tenant access
- clearer ownership boundaries for tenant-managed resources
- easier tenant backup, migration, export, and restore operations
- simpler operational separation between platform operations and tenant workflows
- clearer separation of platform responsibilities and tenant responsibilities

### Negative
- cross-tenant collaboration requires explicit implementation and audit handling
- users participating in multiple tenants require separate tenant memberships
- future federation or organization-spanning identity becomes more complex
- support tooling and analytics require explicit cross-tenant handling
- some duplicated configuration or identity data may exist across tenants

### Operational rules
- tenant-facing routes operate within tenant context
- tenant workspace routes live under `/app`
- tenant authentication requires resolved tenant context
- tenant authorization decisions remain tenant-scoped
- tenant-local roles must not grant Back Office access
- Back Office identities remain operationally independent from tenant identities
- tenant imports and exports must explicitly define tenant scope
- cross-tenant operations must remain explicit
- privileged cross-tenant actions must produce audit records
- tenant context must be visible whenever operational tooling accesses tenant-owned data
