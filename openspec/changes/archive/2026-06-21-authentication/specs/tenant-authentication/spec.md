## ADDED Requirements

### Requirement: Tenant Resolution Precedes Tenant-User Authentication

The system SHALL resolve tenant context before tenant-user authentication is evaluated, and the tenant-authentication firewall SHALL NOT inspect any tenant-local identity, credential, session, or tenant authentication state until tenant context is resolved and the tenant is eligible for tenant-facing access.

#### Scenario: Tenant authenticator runs after tenant resolution

- **WHEN** a request reaches a tenant-facing route
- **THEN** tenant context resolution (kernel.request priority 20) has completed before the tenant-authentication firewall evaluates the tenant user
- **AND** the tenant authenticator selects its identity store from the resolved tenant context

#### Scenario: No tenant identity store access before context is established

- **WHEN** tenant context has not yet been resolved or is not eligible for tenant-facing access
- **THEN** the tenant authenticator does not read any tenant-local identity, credential, session, or tenant authentication state

### Requirement: Tenant Authentication Uses the Identity Store Selected by the Resolved Tenant Context

The system SHALL authenticate tenant users against the tenant identity store selected by the resolved tenant context, and SHALL NOT use a default tenant, stale tenant context, central identity store, or any shared tenant-user identity source.

#### Scenario: Authentication targets the resolved tenant's store

- **WHEN** tenant context resolves to an eligible tenant and a tenant user submits credentials
- **THEN** the credentials are evaluated only against the tenant identity store selected by that resolved tenant context

#### Scenario: No default or stale tenant store is used

- **WHEN** the current resolved tenant context selects tenant A's identity store
- **THEN** tenant authentication does not consult a default tenant store, a previously resolved (stale) tenant store, the central identity store, or any shared tenant-user identity source

### Requirement: Tenant Authentication Fails Closed on Bad Tenant Context

The system SHALL fail closed for tenant authentication when tenant context is missing, unresolved, inactive, suspended, archived, deleted, ambiguous, or unavailable, denying authentication without inspecting any tenant identity store.

#### Scenario: Missing or unresolved tenant context fails closed

- **WHEN** a tenant-facing authentication attempt occurs with missing or unresolved tenant context
- **THEN** tenant authentication is denied
- **AND** no tenant identity store is inspected

#### Scenario: Inactive, suspended, archived, or deleted tenant fails closed

- **WHEN** the resolved tenant is inactive, suspended, archived, or deleted and therefore not eligible for tenant-facing access
- **THEN** tenant authentication is denied
- **AND** no tenant identity store is inspected

#### Scenario: Ambiguous or unavailable tenant context fails closed

- **WHEN** tenant context is ambiguous (resolves to more than one tenant) or unavailable (cannot be established)
- **THEN** tenant authentication is denied
- **AND** no default or fallback tenant is selected and no tenant identity store is inspected

### Requirement: No Fallback Between Tenant and Platform Authentication

The system SHALL NOT silently fall back from tenant authentication to platform authentication, and failed tenant authentication SHALL NOT attempt authentication against the central platform-owned identity store.

#### Scenario: Failed tenant authentication does not attempt platform authentication

- **WHEN** tenant authentication fails because no matching tenant-user identity is found in the resolved tenant's store
- **THEN** the failure is final for the tenant boundary
- **AND** the central platform-owned identity store is not consulted and no platform authentication is attempted as a fallback

### Requirement: Matching Identity Attributes Do Not Cross Tenant or Platform Boundaries

The system SHALL NOT treat a matching email address or other identity attribute that exists in another tenant or in the central platform store as proof of authentication in the resolved tenant.

#### Scenario: Same email in another tenant does not authenticate here

- **WHEN** the same email address exists as a tenant user in tenant B, but no matching tenant-user identity exists in the resolved tenant A's store
- **THEN** tenant authentication in tenant A fails
- **AND** tenant B's identity store is never consulted

#### Scenario: Platform-side matching email does not authenticate as tenant user

- **WHEN** a platform operator has the same email address as a value submitted to the tenant firewall, but no matching tenant-user identity exists in the resolved tenant's store
- **THEN** tenant authentication fails
- **AND** the central platform store is never consulted as proof of authentication

### Requirement: Tenant Authentication State Is Bound to One Resolved Tenant

The system SHALL bind tenant authentication state to the tenant context for which the tenant user authenticated and SHALL reject that state when the currently resolved tenant context does not match.

#### Scenario: Tenant state identifies its tenant

- **WHEN** a tenant user authenticates in tenant A
- **THEN** the resulting tenant authentication state is bound to tenant A's tenant context

#### Scenario: Tenant state rejected on tenant mismatch

- **WHEN** tenant authentication state bound to tenant A is presented while the currently resolved tenant context is tenant B
- **THEN** the tenant authentication state is rejected
- **AND** it does not grant access to tenant B

### Requirement: Existing Tenant Authentication State Is Rejected When the Tenant Becomes Ineligible

The system SHALL reject existing tenant authentication state when the bound tenant is no longer eligible for tenant-facing access, applying tenant lifecycle eligibility both when establishing and when using tenant authentication state.

#### Scenario: Existing tenant state rejected after tenant becomes ineligible

- **WHEN** valid tenant authentication state was established for tenant A and tenant A subsequently becomes inactive, suspended, archived, deleted, or unavailable
- **THEN** the existing tenant authentication state is rejected on use
- **AND** tenant-facing access is denied

### Requirement: Tenant Authentication State Grants No Cross-Boundary Access

The system SHALL ensure tenant authentication state does not grant access to another tenant or to the Back Office, and does not imply platform-operator authentication.

#### Scenario: Tenant state does not grant Back Office access

- **WHEN** a request carrying only tenant authentication state reaches the Back Office platform-authentication firewall
- **THEN** the tenant authentication state does not satisfy platform authentication
- **AND** Back Office access is denied

#### Scenario: Tenant state does not grant another tenant

- **WHEN** tenant authentication state bound to tenant A is used against tenant C's resolved context
- **THEN** access to tenant C is denied
- **AND** no privilege is crossed from tenant A to tenant C
