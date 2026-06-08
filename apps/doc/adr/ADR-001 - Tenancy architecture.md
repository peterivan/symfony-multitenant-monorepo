# ADR-001: Tenancy Architecture

## Status

Accepted

Date: 2026-06-08

## Decision

The application will implement its own tenancy layer for Symfony and Doctrine. The tenancy layer owns
tenant resolution, tenant context lifecycle, and Doctrine database selection. Provisioning,
migrations, and audit logging use this layer but remain separate application services.

The default tenant model is database-per-tenant:

* one shared Symfony application
* one central PostgreSQL database for tenant registry, routing, provisioning, operator, and
  deployment metadata
* one PostgreSQL database per tenant for tenant-owned application data
* separate Doctrine connections, entity managers, mapping boundaries, and repository namespaces for
  central data and tenant-scoped data

HTTP tenant resolution defaults to subdomains, but tenant resolution must be extensible. Custom
resolvers are allowed for application-specific requirements. Resolver order is defined by
configuration, and application startup must fail when resolver ordering is ambiguous. Every resolver
must resolve to no tenant or to a stable tenant identifier used to load tenant metadata.

Tenant context is required before tenant-scoped data access. It must be initialized for each
execution unit and cleared when that unit ends, including failure cases. Execution units include HTTP
requests, console command invocations, message handling attempts, scheduled job runs, and other
isolated units of application work. The tenancy layer is the only component allowed to create,
replace, or clear active tenant context.

Tenant context must be explicitly established for each execution unit. It must not be implicitly
inherited across process boundaries.

The application will not depend on a third-party package as the owner of tenant resolution, context
lifecycle, or database selection.

The central database stores tenant registry, tenant domains, tenant database location, provisioning
state, platform operators, and other cross-tenant infrastructure metadata. Tenant databases store
tenant-local identities, permissions, configuration, business data, and application workflows. Tenant
identifiers must be globally unique across shared and dedicated deployments and immutable after
creation.

Data ownership must be explicit. Data is either central-owned or tenant-owned. Tenant-owned business
data must not be stored in central storage unless the component is explicitly designed as
cross-tenant infrastructure.

Tenant-local identity ownership is intentional. The same email address may exist in multiple tenants,
and authentication, password reset, MFA, roles, and permissions are tenant-scoped unless a later ADR
explicitly introduces a different identity model.

A tenant database belongs to exactly one tenant.

Tenant lifecycle state is centrally managed. The concrete state model may evolve, but it must
represent at least active, suspended, archived, and deleted tenant states before those lifecycle
operations are implemented.

Central and tenant code must remain separated unless a component is explicitly designed and
structured as shared code. Central and tenant Doctrine access must remain distinguishable in
configuration and code. Tenant database selection must happen before tenant EntityManager or
connection use; tenant-scoped access must not fall back to the central database, a default tenant, or
stale tenant context.

The tenancy model supports three isolation tiers:

* Shared DB Infrastructure: shared application deployment with tenant databases on shared PostgreSQL
  infrastructure
* Dedicated DB Infrastructure: shared application deployment with selected tenant databases on
  dedicated PostgreSQL server instances
* Dedicated Application Deployment: fully dedicated application deployment for selected tenants,
  including on-premise deployment

In shared deployments, the central database is the source of truth for tenant registry and routing
metadata. Dedicated and on-premise deployments use their own central database unless explicitly
integrated with the shared platform registry. All tiers must preserve the same central-vs-tenant data
ownership rules.

## Why

The application needs strong tenant isolation while keeping ordinary tenant onboarding and operations
practical. Database-per-tenant gives every tenant at least a database-level isolation boundary and
keeps backup, restore, export, deletion, and future dedicated deployment paths clear.

The custom Symfony/Doctrine layer keeps tenant context explicit and under application control. This
avoids making a third-party tenancy package the authority over routing, execution context, or
database selection.

The main alternatives are schema-per-tenant tenancy, shared-table tenancy, and always-dedicated
deployments. Schema-per-tenant and shared-table tenancy reduce provisioning work, but weaken
isolation and make tenant-level operations more coupled to shared database state. Always-dedicated
deployments maximize isolation, but make standard tenant onboarding and operations heavier than the
application needs by default.

Local development must use the same tenancy model as the application so central and tenant database
behavior can be verified before deployment.

## Non-Goals

This ADR does not decide cross-tenant reporting optimization, global identity management,
multi-region tenant placement, automatic tenant sharding, or tenant federation.

## Consequences

* Tenant-scoped access must fail closed when tenant context is missing, inactive, unresolved, or
  cannot be initialized.
* Tenant provisioning must create or register a tenant database, run tenant migrations, seed required
  tenant baseline data, and activate the tenant only after its database is initialized.
* Tenant metadata must contain enough database location information to open the tenant database, not
  only the database name. If runtime metadata changes are introduced, cache invalidation and stale
  metadata handling must be implemented.
* Tenant migrations are separate from central migrations and must be runnable for one tenant, all
  tenants, and newly provisioned tenants.
* CLI commands, workers, and background jobs must declare whether they run in central context, one
  tenant context, or across tenants through explicit APIs, attributes, or configuration.
* Cross-tenant analytics, support tooling, imports, exports, and maintenance jobs must record audit
  data identifying the selected tenant, initiator, operation, and tenant-owned data scope accessed or
  changed.
* Cross-tenant execution must establish and dispose tenant context per tenant iteration, including
  failure paths.
* Operations spanning central and tenant databases must not assume atomic cross-database
  transactions.
* Tests must cover fail-closed tenant access, deterministic resolver ordering, tenant context cleanup
  after success and failure, no context leakage in long-running processes, inactive tenant rejection,
  and separation between central and tenant Doctrine access paths.

## Revisit When

Revisit this decision if tenant count makes database-per-tenant operations difficult to manage,
cross-tenant reporting becomes a primary product capability, tenant database provisioning becomes too
expensive to operate, dedicated deployments become the default operating model, or a Symfony/Doctrine
tenancy package proves simpler while preserving the same explicit boundaries.

## References

* [ADR-002: Back Office](ADR-002-Back-Office.md)
* [ADR-003: Tenant Workspace](ADR-003-Tenant-workspace.md)
