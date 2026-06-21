# tenant-user-identity Specification

## Purpose
TBD - created by archiving change identity-model. Update Purpose after archive.
## Requirements
### Requirement: Tenant-User Identity Is Tenant-Owned and Tenant-Stored

The system SHALL model tenant-user identity as a tenant-owned identity that lives in tenant storage and is mapped only to the tenant boundary, never to central storage.

#### Scenario: Tenant-user identity mapped to tenant storage

- **WHEN** the tenant-user identity entity and its Doctrine mapping are inspected
- **THEN** the entity is owned by the tenant mapping boundary and bound to the tenant connection/EntityManager from `tenant-database-connectivity`
- **AND** it is not mapped to the `central` connection, central EntityManager, or central store

#### Scenario: Tenant-user identity grants no Back Office access

- **WHEN** the tenant-user identity model is reviewed
- **THEN** it carries tenant-facing attribution only
- **AND** it contains no field that grants Back Office or platform-operator access

### Requirement: Tenant-User Identity Existence and Access Are Scoped to the Selected Tenant

The system SHALL scope tenant-user identity existence and access to the selected tenant context, so that a tenant-user identity is only meaningful and only reachable inside its resolved tenant.

#### Scenario: Tenant-user identity reachable only within its tenant context

- **WHEN** code accesses tenant-user identities
- **THEN** they resolve only through the active resolved tenant context provided by `tenant-database-connectivity`
- **AND** there is no central or cross-tenant path that resolves them without a selected tenant

#### Scenario: Existence in one tenant implies nothing in another

- **WHEN** a tenant-user identity exists in tenant A
- **THEN** it does not create or imply any existence, access, authorization, or identity continuity in tenant B

### Requirement: Same Identity Attribute Across Tenants Does Not Imply Shared Identity or Cross-Tenant Access

The system SHALL allow the same email address or other identity attribute to exist independently in multiple tenants and SHALL NOT treat matching attributes as proof of shared identity, tenant participation, or cross-tenant access.

#### Scenario: Same email in two tenants is two independent identities

- **WHEN** the same email address exists as a tenant-user identity in tenant A and as a tenant-user identity in tenant B
- **THEN** the two are modeled as separate, independent identities owned by their respective tenants
- **AND** the tenant A identity grants no access, authorization, or presence in tenant B
- **AND** no model field, constraint, or relationship links them as one person

#### Scenario: Uniqueness is per-tenant only

- **WHEN** uniqueness constraints on tenant-user identity attributes are reviewed
- **THEN** uniqueness for an attribute such as email is enforced only within a single tenant store
- **AND** no cross-tenant or global uniqueness constraint exists that would link matching attributes across tenants

### Requirement: No Central Tenant-Membership Model

The system SHALL NOT establish tenant-user access through membership in a central identity system and SHALL NOT define a default central identity plus tenant-membership model.

#### Scenario: No central membership grants tenant access

- **WHEN** the identity model is reviewed for how tenant access is established
- **THEN** tenant-user access is established by the tenant-owned identity inside the selected tenant
- **AND** there is no central identity or central membership/assignment record that grants tenant-user access

### Requirement: Tenant-User Profile and Account Recovery Are Tenant-Local

The system SHALL keep tenant-user name, email, profile, and account-recovery state owned and changed within the tenant boundary, with no central mirror or cross-tenant coordination.

#### Scenario: Profile and recovery state stay tenant-local

- **WHEN** tenant-user name, email, profile, or account-recovery state is stored or changed
- **THEN** the change occurs within the tenant store inside the selected tenant context
- **AND** no central record and no other tenant store is updated or coordinated as a result

### Requirement: Audit Attribution Identifies Tenant-User Identity and Selected Tenant Context

The system SHALL provide audit attribution that distinguishes a tenant-user identity and its selected tenant context from platform-operator identity and platform-operation scope when those facts apply.

#### Scenario: Tenant-user-attributed audit record is distinguishable

- **WHEN** an audited action is performed under a tenant-user identity inside a selected tenant
- **THEN** the audit attribution records that the acting identity is a tenant-user identity together with its identity reference
- **AND** it records the selected tenant context
- **AND** the record is distinguishable from one attributed to a platform-operator identity performing a platform operation

