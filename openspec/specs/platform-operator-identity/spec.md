# platform-operator-identity Specification

## Purpose
TBD - created by archiving change identity-model. Update Purpose after archive.
## Requirements
### Requirement: Platform-Operator Identity Is Central-Owned and Central-Stored

The system SHALL model platform-operator identity as a central-owned identity that lives in central storage and is mapped only to the central boundary, never to any tenant store.

#### Scenario: Operator identity mapped to central storage

- **WHEN** the platform-operator identity entity and its Doctrine mapping are inspected
- **THEN** the entity is owned by the central mapping boundary and bound to the `central` connection/EntityManager from `central-database-foundation`
- **AND** it is not mapped to any tenant connection, tenant EntityManager, or tenant store

#### Scenario: No tenant access reaches operator identity

- **WHEN** code attempts to load platform-operator identities through a tenant connection or tenant context
- **THEN** there is no mapping or relationship that resolves operator identities from tenant storage

### Requirement: Platform-Operator Identity Is Scoped to the Platform Boundary Only

The system SHALL scope platform-operator identity to the platform/Back Office boundary (ADR-002) and SHALL NOT model any tenant-user identity or tenant-facing access on the operator identity.

#### Scenario: Operator identity carries no tenant access fields

- **WHEN** the platform-operator identity model is reviewed
- **THEN** it carries platform/Back Office attribution only
- **AND** it contains no tenant-user identity fields and no fields granting tenant-facing or tenant-local access

#### Scenario: Operator identity does not imply tenant-user identity

- **WHEN** a platform-operator identity exists
- **THEN** it does not by itself constitute, reference, or create any tenant-user identity in any tenant
- **AND** it grants no tenant-facing application access

### Requirement: Operator Identity Is Not Merged With Tenant-User Identity by Matching Attributes

The system SHALL keep platform-operator identity separate from tenant-user identity and SHALL NOT merge them, or treat them as the same person, on the basis of matching identity attributes such as a shared email address.

#### Scenario: Same email as operator and tenant user stays separate

- **WHEN** the same email address exists both as a platform-operator identity and as a tenant-user identity in some tenant
- **THEN** the two identities are modeled as separate, independent identities
- **AND** neither implies the existence, access, or authorization of the other
- **AND** no model field, constraint, or relationship links them as one person

#### Scenario: No global "same person" constraint

- **WHEN** uniqueness constraints across stores are reviewed
- **THEN** no cross-store or platform-level uniqueness constraint exists that would force a single shared identity for a matching attribute across the operator and tenant boundaries

### Requirement: Audit Attribution Identifies Platform-Operator Identity and Platform Operations

The system SHALL provide audit attribution that distinguishes a platform-operator identity and the platform-operation scope from tenant-user identity and selected tenant context when those facts apply.

#### Scenario: Operator-attributed audit record is distinguishable

- **WHEN** an audited action is performed under a platform-operator identity
- **THEN** the audit attribution records that the acting identity is a platform-operator identity together with its identity reference
- **AND** it records the platform-operation scope when the action is a platform operation
- **AND** the record is distinguishable from one attributed to a tenant-user identity acting in a selected tenant context

