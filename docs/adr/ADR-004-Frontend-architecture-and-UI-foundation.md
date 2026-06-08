# ADR-004: Frontend Architecture and UI Foundation

## Status
Accepted  
Date: 2026-05-22

## Context
Proxia.Plan has separate application sections with different users and workflows. ADR-002 defines
the Back Office boundary, and ADR-003 defines the tenant workspace boundary.

This ADR defines only the shared frontend foundation used inside those boundaries. It does not
define route ownership, tenant isolation, Back Office permissions, or workspace authorization.

Without a shared frontend foundation, the project risks:
- duplicated component implementations
- incompatible interaction conventions
- divergent frontend architectures
- divergent state management approaches
- higher maintenance costs
- accessibility inconsistencies
- increased onboarding complexity for frontend development

## Decision
The frontend foundation is based on:
- Laravel
- Inertia.js
- Vue 3
- Vuetify as the primary UI framework
- Material Design as the default design language
- Pinia as the standard client-side state management solution
- XState for complex workflow, orchestration, and finite-state interactions

Laravel with Inertia.js is the primary application delivery model for frontend applications.

Frontend applications are primarily server-driven SPA-style applications where Laravel is
responsible for:
- routing
- middleware and authorization
- backend orchestration
- API and domain access
- server-side application composition
- Inertia responses and page delivery

Vue applications are responsible for:
- interactive UI behavior
- client-side state handling
- workflow interactions
- frontend presentation logic

Frontend implementations should prioritize standard Vuetify components, layouts, theming, and
interaction patterns before introducing custom UI implementations.

Local Vue component state and composables remain appropriate for page-local behavior and should
be preferred over introducing global state unnecessarily.

Pinia is the standard solution for:
- shared application state
- API-backed entity state
- UI state shared across components
- general frontend application state management

Pinia may hold client-side projections of session, authentication, and shared UI state, but Laravel
remains the authority for authentication and authorization.

XState is the preferred solution for:
- complex workflows
- multi-step interactions
- async orchestration
- stateful UI flows
- explicit finite-state modeling
- long-running or interruptible frontend processes

XState must not replace Pinia as the primary application state container.

XState should be used when a workflow has explicit states, guarded transitions, retries,
cancellation, parallel states, or branching outcomes. It is preferred over ad hoc boolean flags,
chained watchers, or implicit event sequencing for those workflows.

XState must not be used for simple form state, basic loading flags, menu visibility, or ordinary
component-local interactions.

Custom-built components are allowed only when:
- no suitable Vuetify component exists
- domain-specific functionality cannot reasonably be implemented using standard components
- technical integration requirements require custom implementation

Custom styling must remain visually and behaviorally compatible with Material Design principles.

Frontend implementations must not introduce:
- alternative frontend application architectures
- competing component frameworks
- competing design systems
- isolated visual languages per application section
- custom styling architectures that bypass or replace Vuetify conventions
- additional primary frontend state management solutions

## Consequences

### Positive
- consistent frontend architecture across the platform
- shared component vocabulary and interaction patterns
- predictable frontend development model
- standardized state management patterns
- clearer modeling of complex workflows
- simplified backend/frontend integration
- easier onboarding for frontend developers
- reduced visual and architectural fragmentation
- simpler long-term frontend maintenance

### Negative
- reduced frontend architectural flexibility
- some advanced UX patterns may require adaptation to fit Vuetify conventions
- XState introduces additional architectural complexity
- developers must understand both reactive and finite-state paradigms
- Inertia introduces tighter coupling between Laravel and frontend delivery
- custom UI experimentation requires stronger justification

### Operational rules
- Laravel with Inertia.js is the mandatory frontend application delivery model
- Vuetify is the primary frontend UI framework
- Material Design is the default visual and interaction language
- local Vue component state and composables remain acceptable for page-local behavior
- Pinia is the standard general-purpose frontend state store
- XState is the preferred solution for complex workflow orchestration
- Laravel remains the authority for authentication and authorization
- frontend applications must prefer standard Vuetify components over custom implementations
- custom UI components require explicit justification
- competing frontend architectures must not be introduced
- competing UI frameworks must not be introduced
- additional primary frontend state management solutions must not be introduced
- custom styling must remain compatible with the shared design language
- route ownership, tenant boundaries, Back Office boundaries, and authorization rules remain owned
  by their dedicated ADRs
