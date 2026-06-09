# ADR-005: PostgreSQL Platform Standard

## Status

Accepted

Date: 2026-06-09

## Owner

The database foundation owns this decision. Schema design, indexing strategy, query capabilities,
database-side automation, auditing support, tenant database implementation, schema definitions, and
PostgreSQL operational requirements must integrate with this boundary instead of assuming
cross-database portability.

## Decision

PostgreSQL is the required database platform for the application. Database portability is not a
project goal, and supporting multiple database engines is not a project goal.

Central and tenant databases are PostgreSQL databases. Database design decisions should optimize for
PostgreSQL correctness, maintainability, operational simplicity, observability, and performance
instead of targeting the lowest common denominator across database engines.

Application architecture may intentionally use PostgreSQL-specific capabilities when they provide a
clear design benefit. Doctrine usage must not prevent appropriate PostgreSQL feature adoption. Doctrine
abstractions are useful for mapping, unit-of-work behavior, query composition, and integration with
Symfony, but they are not a portability requirement.

Appropriate PostgreSQL capabilities are allowed, including:

* JSONB
* GIN indexes
* GiST indexes
* partial indexes
* expression indexes
* generated columns
* materialized views
* CTEs
* recursive CTEs
* window functions
* advisory locks
* triggers
* stored procedures and functions where appropriate
* extensions approved by the platform
* PostgreSQL full-text search

These capabilities are permitted but not mandatory. They should be used when they improve the design
relative to simpler application-side alternatives.

Some PostgreSQL capabilities introduce significant complexity and require strong justification before
adoption. Examples include table inheritance, rule-based application behavior, unusual procedural
language integrations, and features that materially increase operational complexity without clear
benefit. For application behavior that belongs in the database, triggers and functions are preferred
over PostgreSQL rules because their execution model is more explicit and easier to reason about.

## Why

ADR-001 already standardizes the tenancy model on one central PostgreSQL database and one PostgreSQL
database per tenant. Treating PostgreSQL as a platform dependency makes that decision explicit beyond
tenant storage layout.

The database-per-tenant architecture benefits from PostgreSQL operational consistency.
Provisioning, schema changes, audit logging, indexing, reporting, backup, restore, export, deletion,
and tenant maintenance are easier to design when central and tenant databases share one database
platform and one operational model.

PostgreSQL provides mature features that can improve correctness, maintainability, observability, and
performance. Artificial portability constraints would reduce available design options without
providing meaningful value to the application.

The main alternative is to keep database usage portable across multiple engines. That would preserve
the theoretical option to migrate to another database platform, but it would also weaken the
architecture's ability to use PostgreSQL capabilities where they are the simplest or most correct
solution.

## Consequences And Invariants

* PostgreSQL operational and development knowledge is required to work effectively within the
  platform.
* Future migration to another database engine becomes more expensive.
* Central and tenant database implementations may assume PostgreSQL availability.
* Database design may use PostgreSQL-specific capabilities when they are appropriate for the problem.
* Doctrine abstractions must not be treated as a reason to avoid PostgreSQL-native solutions.
* Schema changes may introduce PostgreSQL-specific indexes, constraints, generated columns,
  functions, triggers, materialized views, extensions, and other approved database objects.
* Query implementations may use PostgreSQL-specific SQL when it improves correctness,
  maintainability, observability, or performance.
* Operational tooling, deployment processes, provisioning, schema tooling, audit support, backup,
  restore, export, and reporting may assume PostgreSQL behavior.
* Database portability must not be introduced as a design constraint without a superseding ADR.
* Workarounds whose sole purpose is preserving theoretical support for other database engines are not
  accepted.
* PostgreSQL-specific feature usage must still be reviewed for maintainability, testability,
  operational impact, and clarity of ownership.
* Complex PostgreSQL features that materially increase operational risk require explicit
  architectural justification.
* This ADR does not define the tenant database lifecycle, tenant context ownership, central-vs-tenant
  data ownership, audit record structure, schema evolution ownership, migration orchestration,
  backup policy, retention policy, or approved extension list.

## Validation

Important invariants should be enforced through architecture review, database design review, schema
review, and code review of persistence and operational tooling changes.

Expected validation includes:

* Code review must reject changes that introduce database portability as a design constraint without
  a superseding ADR.
* Code review must reject workarounds whose sole purpose is preserving theoretical support for other
  database engines.
* Database design review must verify that schema, indexing, query, automation, and database-object
  choices optimize for PostgreSQL instead of a lowest-common-denominator database model.
* Review must verify that Doctrine usage does not prevent legitimate PostgreSQL feature adoption.
* PostgreSQL-specific feature usage must be reviewed for maintainability, operational impact, failure
  behavior, observability, and backup or schema-change implications where relevant.
* Complex features such as table inheritance, rule-based application behavior, unusual procedural
  language integrations, or high-operational-impact extensions must receive explicit architectural
  justification before adoption.
* Schema changes that introduce PostgreSQL extensions, generated columns, triggers, functions,
  materialized views, or advanced indexes must make those database objects visible and reviewable.

## Revisit When

Revisit this decision if the application must support a non-PostgreSQL deployment target, if
PostgreSQL operational requirements become unsuitable for the supported deployment model, if a later
ADR introduces a storage architecture that is not PostgreSQL-based, or if PostgreSQL-specific feature
usage creates operational costs that outweigh the benefits of a single database platform.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy Architecture.md>)
