# Project Glossary

Project vocabulary for architecture, implementation, review, and documentation.

This is not an ADR. Accepted ADRs remain the source of truth for architectural decisions.

## Boundaries And Contexts

| Term | Meaning |
| --- | --- |
| Architectural Boundary | Durable ownership line for a project concern. |
| Back Office Boundary | Boundary for central platform operations and platform-operator access. |
| Central Context | Runtime execution without an active tenant context. |
| Database Foundation | Boundary that owns PostgreSQL as the project database platform. |
| Frontend Foundation | Shared Vue, TypeScript, Vuetify, Pinia, XState, and Vite foundation. |
| Identity Boundary | Boundary that keeps platform-operator and tenant-user identities separate. |
| Platform Context | Central context when discussing Back Office or platform operations. |
| Tenant Application Boundary | Boundary for tenant-facing users, authorization, configuration, data, and workflows. |

## Tenancy

| Term | Meaning |
| --- | --- |
| Back Office | Central operational surface for platform operators, exposed under `/bo`. |
| Cross-Tenant Operation | Operation that acts across tenant boundaries or iterates through tenants. |
| Database-per-Tenant | Tenancy model where each tenant owns a separate PostgreSQL database. |
| Execution Unit | Isolated unit of work, such as a request, command, worker run, or job attempt. |
| Fail Closed | Deny access or stop work when required tenant context cannot be proven. |
| Platform Operation | Explicit Back Office operation performed by a platform operator. |
| Tenant | Customer or organizational unit isolated by the platform and represented by central metadata and one tenant database. |
| Tenant Application | Tenant-facing application surface inside the Tenant Application Boundary. |
| Tenant Context | Runtime state selecting one tenant for tenant-scoped execution. |
| Tenant Domain | Domain or host metadata used to route requests to a tenant. |
| Tenant-Facing Route | Route exposed to tenant users and requiring tenant context. |
| Tenant Identifier | Globally unique, immutable identifier used to load tenant metadata. |
| Tenant Isolation | Separation that prevents tenant data, identity, context, or access from leaking across tenants. |
| Tenant Lifecycle State | Central state describing whether tenant-facing access is allowed. |
| Tenant Provisioning | Platform operation that creates or registers tenant infrastructure. |
| Tenant Registry | Central metadata used to locate, route, provision, and manage tenants. |
| Tenant Resolution | Process that resolves execution to no tenant or one tenant identifier. |
| Tenant Resolver | Component that participates in tenant resolution. |
| Tenant Scope | The selected tenant and data range for a tenant-impacting operation. |
| Tenancy Layer | Component owning tenant resolution, context lifecycle, and database selection. |
| Tenant-Scoped Execution | Runtime execution inside an active tenant context. |

## Data And Infrastructure

| Term | Meaning |
| --- | --- |
| Audit Record | Record of a security-sensitive or tenant-impacting operation. |
| Central Database | PostgreSQL database for platform-owned metadata and infrastructure state. |
| Central-Owned Data | Data owned by the central platform boundary. |
| Cross-Tenant Infrastructure | Shared infrastructure that legitimately carries cross-tenant metadata or scoped data. |
| Database Portability | Goal of supporting multiple database engines; not a project goal. |
| Data Ownership | Rule that each data set belongs to either the central platform or one tenant. |
| Identity Store | Storage location for identities used by one authentication boundary. |
| PostgreSQL Platform Standard | Decision that central and tenant databases are PostgreSQL. |
| Shared Infrastructure | Infrastructure used across tenants or boundaries. |
| Tenant Database | PostgreSQL database owned by exactly one tenant. |
| Tenant Migration | Schema or data migration applied to tenant databases separately from central migrations. |
| Tenant-Owned Data | Data owned by an individual tenant. |

## Identity, Authentication, And Authorization

| Term | Meaning |
| --- | --- |
| Authentication | Proving control of an identity within its boundary. |
| Authentication Boundary | Boundary that scopes authentication rules, state, and identity-store selection. |
| Authentication State | State representing an authenticated identity within one authentication boundary. |
| Authorization | Decision about what an authenticated identity may do. |
| Identity | Project identity record for a platform operator or tenant user. |
| Identity Attribute | Value such as email address or name used to describe an identity. |
| Identity Model | Ownership model that defines where identities live and how they relate. |
| Platform Authentication | Authentication of a platform operator for Back Office access. |
| Platform Operator | Platform-owned identity that accesses Back Office. |
| Tenant Authentication | Authentication of a tenant user inside resolved tenant context. |
| Tenant User | Tenant-owned identity that accesses tenant-facing application surfaces. |

## Tenant-Local Terms

| Term | Meaning |
| --- | --- |
| Tenant-Facing | Exposed to or used by tenant-scoped users inside tenant context. |
| Tenant-Local | Owned by, scoped to, and evaluated inside one tenant boundary. |
| Tenant-Local Membership | Tenant-owned relationship or assignment inside one tenant boundary. |
