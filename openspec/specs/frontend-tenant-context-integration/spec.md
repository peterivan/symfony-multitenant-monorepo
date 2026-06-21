# frontend-tenant-context-integration Specification

## Purpose
TBD - created by archiving change frontend-foundation. Update Purpose after archive.
## Requirements
### Requirement: Frontend Reflects Resolved Backend Tenant Context

The system SHALL derive frontend tenant context exclusively from the tenant context resolved by the backend, and SHALL NOT redefine tenant resolution or tenant-context lifecycle client-side.

#### Scenario: Frontend tenant context mirrors backend resolution

- **WHEN** the frontend establishes or displays tenant context
- **THEN** that tenant context matches the tenant context resolved and provided by the backend

#### Scenario: Frontend does not redefine tenant resolution

- **WHEN** frontend code attempts to resolve a tenant from client-side inputs (such as URL parsing, local storage, or user selection) without backend resolution
- **THEN** the change is rejected because tenant resolution and lifecycle are owned by the backend (ADR-001/ADR-003)

### Requirement: Frontend Must Not Invent Tenant Context

The system SHALL NOT invent tenant context in frontend state; frontend tenant context MUST originate from backend-resolved context.

#### Scenario: No invented tenant

- **WHEN** the backend has not provided a resolved tenant context
- **THEN** the frontend does not fabricate, infer, or default to a tenant context

### Requirement: Frontend Must Not Silently Switch Tenant Context

The system SHALL change tenant context only from a backend-resolved response, and SHALL NOT silently switch the active tenant client-side.

#### Scenario: Tenant switch is backend-driven

- **WHEN** a tenant switch is requested
- **THEN** the request is sent to the backend and the frontend updates its active tenant context only from the backend's resolved response

#### Scenario: Silent client-side switch is rejected

- **WHEN** frontend code changes the active tenant by directly mutating client state to a different tenant without a backend-resolved response
- **THEN** the change is rejected as a silent client-side tenant switch

### Requirement: Frontend Must Not Persist Tenant Context That Bypasses Backend Resolution

The system SHALL NOT persist tenant context in a way that bypasses backend tenant resolution or that can outlive or contradict the backend execution context.

#### Scenario: No bypassing persistence

- **WHEN** frontend code stores tenant context (for example in local storage, cookies, or a long-lived store) such that it could be reused without re-confirming backend resolution
- **THEN** the change is rejected because persisted tenant context must not outlive or contradict backend-resolved context

#### Scenario: Stale persisted context is not trusted

- **WHEN** previously stored frontend tenant context no longer matches the backend-resolved context
- **THEN** the frontend discards the stale context and uses the backend-resolved context

### Requirement: Frontend Fails Closed on Unknown or Ambiguous Tenant Context

The system SHALL fail closed when the resolved tenant context is unknown, ambiguous, or stale, treating tenant context as absent and not presenting or acting on tenant-scoped state.

#### Scenario: Unknown tenant context fails closed

- **WHEN** the backend-resolved tenant context is unknown or unavailable for a tenant-scoped surface
- **THEN** the frontend treats tenant context as absent, withholds tenant-scoped data and actions, and surfaces the unresolved state rather than proceeding

#### Scenario: Ambiguous tenant context fails closed

- **WHEN** the resolved tenant context is ambiguous or contradicts persisted client state
- **THEN** the frontend does not act on tenant-scoped state and defers to backend re-resolution

