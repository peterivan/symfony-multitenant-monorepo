## ADDED Requirements

### Requirement: Platform Operators Authenticate Against the Central Platform-Owned Identity Store

The system SHALL authenticate platform operators for Back Office access against the central platform-owned identity store in central context, through a dedicated platform-authentication firewall, and SHALL NOT authenticate platform operators against any tenant-owned identity store.

#### Scenario: Platform operator authenticates against the central store

- **WHEN** a platform operator submits credentials to the Back Office (`/bo`) platform-authentication firewall
- **THEN** the credentials are evaluated only against the central platform-owned identity store
- **AND** authentication succeeds only if a matching platform-operator identity exists in the central store

#### Scenario: Platform authentication never reads a tenant identity store

- **WHEN** platform authentication is evaluated
- **THEN** no tenant-owned identity store, tenant credential, or tenant authentication state is read at any point in the platform authentication flow

### Requirement: Back Office Entry Does Not Require Tenant Resolution

The system SHALL allow entry to the Back Office platform-authentication boundary in central context without requiring tenant resolution, and tenant-resolution failure SHALL NOT block or alter Back Office authentication.

#### Scenario: Back Office authenticates with no resolved tenant context

- **WHEN** a request reaches the Back Office (`/bo`) platform-authentication firewall with no resolved tenant context
- **THEN** platform authentication proceeds in central context against the central platform-owned identity store
- **AND** the absence of tenant context does not cause platform authentication to fail or be denied

#### Scenario: Tenant resolution failure does not affect Back Office

- **WHEN** tenant resolution is missing, unresolved, ambiguous, or unavailable for a Back Office request
- **THEN** platform authentication is unaffected and is still evaluated only against the central platform-owned identity store

### Requirement: No Fallback Between Platform and Tenant Authentication

The system SHALL NOT silently fall back from platform authentication to tenant authentication, and failed platform authentication SHALL NOT attempt authentication against any tenant identity store.

#### Scenario: Failed platform authentication does not attempt tenant authentication

- **WHEN** platform authentication fails because no matching platform-operator identity is found in the central store
- **THEN** the failure is final for the platform boundary
- **AND** no tenant identity store is consulted and no tenant authentication is attempted as a fallback

### Requirement: Matching Identity Attributes Do Not Cross Into the Platform Boundary

The system SHALL NOT treat a matching email address or other identity attribute that exists in a tenant identity store as proof of platform-operator authentication.

#### Scenario: Tenant-side matching email does not authenticate as platform operator

- **WHEN** a tenant user has the same email address as a value submitted to the Back Office platform-authentication firewall, but no matching platform-operator identity exists in the central store
- **THEN** platform authentication fails
- **AND** the matching tenant-side attribute is never consulted or used as proof of authentication

### Requirement: Platform Authentication State Is Central and Isolated

The system SHALL scope platform authentication state to central Back Office access, keep it isolated from tenant authentication state, and SHALL NOT let platform authentication state grant tenant-user identity or ordinary tenant-facing application access.

#### Scenario: Platform state grants only Back Office access

- **WHEN** a platform operator holds valid platform authentication state
- **THEN** that state authorizes the Back Office boundary in central context
- **AND** that state does not establish any tenant-user identity

#### Scenario: Platform state does not grant tenant-facing access

- **WHEN** a request carrying only platform authentication state reaches a tenant-facing route under the tenant firewall
- **THEN** the platform authentication state does not satisfy tenant authentication
- **AND** the request is not granted ordinary tenant-facing application access on the basis of platform authentication state
