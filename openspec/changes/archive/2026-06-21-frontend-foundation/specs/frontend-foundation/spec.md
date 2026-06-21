## ADDED Requirements

### Requirement: Shared Frontend Stack Defaults

The system SHALL provide a single shared frontend foundation whose default stack is Vue 3, TypeScript, Vuetify, Pinia, XState, and Vite, used by browser-based application surfaces unless a later project-specific ADR defines a different foundation.

#### Scenario: Default stack is established

- **WHEN** the shared frontend foundation is inspected
- **THEN** Vue 3 is the UI framework, TypeScript is the application language, Vuetify is the default UI component framework, Pinia is the default shared client-side state store, XState is available for explicit workflow orchestration, and Vite is the build and development tooling

#### Scenario: Surfaces build on the shared foundation

- **WHEN** the Back Office or a tenant-facing browser surface is introduced
- **THEN** it builds on the shared frontend foundation and its default stack
- **AND** it does not introduce a separate per-surface frontend foundation

#### Scenario: Competing primary framework is rejected without a superseding ADR

- **WHEN** a change introduces a competing primary UI framework, general-purpose client-side state store, or build system in place of Vuetify, Pinia, or Vite
- **THEN** the change is rejected unless a superseding ADR authorizes the replacement

### Requirement: Dedicated Frontend Workspace

The system SHALL host the shared frontend foundation in a dedicated frontend workspace at `apps/web`, scaffolded with Vite and npm, without moving existing backend code out of `apps/server`.

#### Scenario: Frontend workspace exists and is separate from the backend

- **WHEN** the repository workspaces are inspected
- **THEN** the shared frontend foundation lives in `apps/web` with its own Vite and npm tooling
- **AND** the Symfony backend in `apps/server` is unchanged and no backend code is moved into `apps/web`

#### Scenario: Frontend builds with its own toolchain

- **WHEN** the frontend production build is run from `apps/web`
- **THEN** Vite produces a build using the frontend's npm toolchain independently of the backend Docker (`docker compose exec app`) toolchain

### Requirement: Shared Packages Consumable Across Surfaces

The system SHALL keep shared frontend packages, UI components, layouts, stores, workflow machines, API clients, and route-metadata/tenant-context consumers usable across Back Office and tenant-facing surfaces unless a component is intentionally boundary-specific.

#### Scenario: Shared component is cross-surface

- **WHEN** a shared component or layout is added to the foundation without being marked intentionally boundary-specific
- **THEN** it is consumable by both Back Office and tenant-facing surfaces

#### Scenario: Boundary-specific component is explicit

- **WHEN** a component is intended for only one surface
- **THEN** it is explicitly marked as boundary-specific rather than silently restricting an otherwise shared package

### Requirement: Backend Is the Source of Truth for Route Exposure, Capabilities, Auth, and Tenant Context

The system SHALL derive frontend route exposure, capabilities, authentication state, and tenant context from backend-provided state, and SHALL NOT duplicate or redefine those backend-owned decisions client-side.

#### Scenario: API clients and navigation consume backend-provided state

- **WHEN** shared API clients, route metadata, or navigation state determine which routes, capabilities, or surfaces are exposed
- **THEN** they consume backend-provided route exposure, capabilities, authentication state, and tenant context

#### Scenario: Duplicating backend-owned decisions is rejected

- **WHEN** frontend code reimplements route exposure, authentication, authorization, or tenant-context decisions client-side instead of consuming backend-provided state
- **THEN** the change is rejected as duplicating a backend-owned decision

### Requirement: Client-Side Authorization Checks Are Non-Authoritative

The system SHALL treat client-side authorization and capability checks as presentation-only behavior and SHALL NOT treat them as an access-control boundary; the backend remains the authorization enforcement point.

#### Scenario: Client checks affect presentation only

- **WHEN** the frontend evaluates a client-side authorization or capability check
- **THEN** it may hide or disable UI affordances for usability
- **AND** it does not treat the result as the authoritative access decision

#### Scenario: Client check is never the access boundary

- **WHEN** frontend code treats a client-side authorization check as the source of truth for Back Office or tenant-facing access
- **THEN** the change is rejected and backend enforcement remains required

#### Scenario: UI degrades safely when client and backend disagree

- **WHEN** a client-side check would allow an action but the backend denies it
- **THEN** the backend denial is honored and the frontend surfaces the denial rather than performing the action

### Requirement: XState Reserved for Explicit Workflows

The system SHALL reserve XState for workflows with explicit states, transitions, guards, retries, cancellation, branching outcomes, or long-running orchestration, and SHALL NOT use XState as the general-purpose application state store in place of Pinia.

#### Scenario: XState used for an explicit workflow

- **WHEN** a feature requires explicit finite-state workflow orchestration
- **THEN** XState is an appropriate choice for modeling that workflow

#### Scenario: XState not used as the general store

- **WHEN** general shared application state or simple local UI state is modeled
- **THEN** Pinia (or component-local state/composables) is used and XState is not introduced as the general state store

### Requirement: Cross-Surface Accessibility, Responsiveness, and Interaction Consistency

The system SHALL ensure shared components and layouts preserve consistent accessibility, responsive behavior, and interaction patterns across Back Office and tenant-facing surfaces.

#### Scenario: Shared component preserves consistency

- **WHEN** a shared component or layout becomes cross-surface foundation code
- **THEN** it preserves consistent accessibility, responsive behavior, and interaction patterns across Back Office and tenant-facing surfaces

#### Scenario: Inconsistent shared component is rejected

- **WHEN** a shared component introduces inconsistent accessibility, responsive behavior, or interaction patterns across surfaces
- **THEN** the change is rejected until consistency is restored
