# ADR-001: Tenancy Architecture

## Status

Accepted

Date: 2026-06-08

## Owner

The tenancy boundary owns this decision. Doctrine configuration, tenant resolution, tenant context,
provisioning, migrations, audit logging, background execution, and tenant-aware infrastructure must
integrate with that boundary instead of redefining tenant ownership or context rules.

## Decision

The application will implement its own tenancy layer for Symfony and Doctrine. The tenancy layer owns
tenant resolution, tenant context lifecycle, and Doctrine database selection. Provisioning,
migrations, and audit logging use this layer but remain separate application services.

The default tenant model is database-per-tenant:

* one shared codebase and deployment artifact
* one central PostgreSQL database for tenant registry, routing, provisioning, platform operators, and
  deployment metadata
* one PostgreSQL database per tenant for tenant-owned application data
* separate Doctrine connections, entity managers, and mapping boundaries for central data and
  tenant-scoped data

HTTP tenant resolution defaults to subdomains, but tenant resolution must be extensible. Custom
resolvers are allowed for application-specific requirements. Resolver order is defined by
configuration, and application startup must fail when resolver behavior is ambiguous or invalid.
Every resolver must resolve to no tenant or to a stable tenant identifier used to load tenant
metadata.

Tenant context is required before tenant-scoped data access. It must be explicitly initialized for
each execution unit and cleared when that unit ends, including failure cases. Execution units include
HTTP requests, console command invocations, message handling attempts, scheduled job runs, and other
isolated units of application work. The tenancy layer is the only component allowed to create,
replace, or clear active tenant context.

Tenant context must not be implicitly inherited across process boundaries. Async boundaries such as
Messenger messages, queues, and scheduled jobs must strip active tenant context by default and
re-establish tenant context only from explicit routing metadata, job attributes, or command options.

The application will not depend on a third-party package as the owner of tenant resolution, context
lifecycle, or database selection. Replacing this ownership with a third-party package requires a
superseding ADR.

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
stale tenant context. Tenant-scoped data access must be mediated by the active tenant context and
tenancy infrastructure.

The tenancy model supports three isolation tiers:

* Shared DB Infrastructure: shared application deployment with tenant databases on shared PostgreSQL
  infrastructure
* Dedicated DB Infrastructure: shared application deployment with selected tenant databases on
  dedicated PostgreSQL server instances
* Dedicated Application Deployment: fully dedicated application deployment for selected tenants,
  including isolated runtime instances and on-premise deployment

In shared deployments, the central database is the source of truth for tenant registry and routing
metadata. Dedicated and on-premise deployments either use their own central database without registry
synchronization, or integrate with the shared platform registry through an explicit federation
mechanism. All tiers must preserve the same central-vs-tenant data ownership rules.

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

## Consequences And Invariants

* Tenant-scoped access must fail closed when tenant context is missing, unresolved, inactive, or
  cannot be initialized.
* Tenant provisioning must create or register a tenant database, run tenant migrations, seed required
  tenant baseline data, and activate the tenant only after its database is initialized.
* Tenant metadata must contain enough database location information to open the tenant database, not
  only the database name. If runtime metadata changes are introduced, cache invalidation and stale
  metadata handling must be implemented.
* Tenant migrations are separate from central migrations and must be runnable for one tenant, all
  tenants, and newly provisioned tenants. Migration orchestration must track tenant migration state.
* CLI commands, workers, and background jobs must declare whether they run in central context, a
  single tenant context, or across tenants through explicit APIs, attributes, or configuration.
* Cross-tenant analytics, support tooling, imports, exports, and maintenance jobs must record audit
  data identifying the selected tenant, initiator, operation, and tenant-owned data scope accessed or
  changed.
* Audit logs, error traces, and platform telemetry are cross-tenant infrastructure. They live in
  central storage and must minimize tenant business data and PII, storing only what is required for
  operational, compliance, security, or support purposes.
* Cross-tenant execution must establish and dispose tenant context per tenant iteration, including
  failure paths.
* Tenant-scoped execution must release tenant Doctrine resources when the execution unit ends.
* Shared caches, session storage, file/object storage, and other shared infrastructure must be
  tenant-namespaced or routed per tenant whenever they contain tenant-scoped data.
* Operations spanning central and tenant databases must not assume atomic cross-database
  transactions.
* This ADR does not decide cross-tenant reporting optimization, global identity management,
  multi-region tenant placement, automatic tenant sharding, or tenant federation implementation.

## Validation

Important invariants should be enforced through focused tests, static analysis where practical, and
explicit code review checks for tenant boundary changes.

Expected validation includes:

* Tests must cover fail-closed tenant access, deterministic resolver ordering, tenant context cleanup
  after success and failure, no context leakage in long-running processes, inactive tenant rejection,
  and separation between central and tenant Doctrine access paths.
* Code review must reject tenant-scoped data access that bypasses the tenancy layer, silently falls
  back to central storage, or relies on implicit tenant context.
* Code review must reject shared cache, session, file, object storage, queue, or background execution
  changes that carry tenant-scoped data without explicit tenant isolation or context propagation
  rules.

## Revisit When

Revisit this decision if tenant count makes database-per-tenant operations difficult to manage,
cross-tenant reporting becomes a primary product capability, tenant database provisioning becomes too
expensive to operate, dedicated deployments become the default operating model, or a Symfony/Doctrine
tenancy package proves simpler while preserving the same explicit boundaries.
