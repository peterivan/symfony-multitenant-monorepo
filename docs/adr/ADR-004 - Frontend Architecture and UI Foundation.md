# ADR-004: Frontend Architecture and UI Foundation

## Status

Accepted

Date: 2026-06-09

## Owner

The frontend foundation owns this decision. Frontend application shells, shared packages, UI
components, client-side state, workflow orchestration, frontend authentication integration,
tenant-context integration, API integration, build tooling, and frontend test conventions must
integrate with this boundary instead of introducing separate frontend foundations per application
surface.

## Decision

The application will provide a shared Vue 3 frontend foundation for browser-based application
surfaces. Back Office functionality is implemented using this frontend foundation. Tenant-facing
applications use the same frontend foundation when introduced by this application unless a
project-specific ADR defines a different frontend foundation.

The frontend foundation standardizes the primary frontend stack:

* Vue 3 for interactive application UI
* TypeScript for frontend application code
* Vuetify as the primary UI component framework
* Pinia for shared client-side application state
* XState for explicit workflow and finite-state orchestration
* Vite for frontend build and development tooling

Shared frontend packages, reusable UI components, application layouts, client-side state stores,
workflow machines, API clients, authentication integration, tenant-context integration, route
metadata consumption, build configuration, and frontend test utilities belong to this boundary.

The frontend foundation does not own tenancy architecture, tenant context lifecycle, Back Office
authorization, tenant-local authorization, route exposure, or data ownership. Those boundaries remain
defined by ADR-001, ADR-002, and ADR-003. Frontend code must consume those backend and runtime
boundaries instead of redefining them client-side.

## Why

The application needs one coherent frontend foundation so Back Office and tenant-facing application
surfaces can share UI vocabulary, state-management patterns, workflow modeling, build tooling, and
integration conventions without coupling tenancy architecture to a specific user interface.

Vue 3, TypeScript, Vuetify, Pinia, XState, and Vite provide a practical baseline for a
component-driven administrative and tenant-facing UI while leaving backend tenancy ownership in
Symfony and the tenancy ADRs. Standardizing the foundation avoids fragmented frontend architectures
and competing UI systems while still allowing project-specific ADRs to define tenant-facing
workspace structure, navigation, and product behavior.

The main alternative is to leave frontend technology and structure fully project-specific. That would
make the application less opinionated, but it would also make shared Back Office development,
authentication integration, tenant-context integration, and reusable UI packages harder to maintain.

## Consequences And Invariants

* Back Office frontend functionality uses the shared frontend foundation.
* Tenant-facing frontend functionality uses the shared frontend foundation unless a project-specific
  ADR defines a different frontend foundation. ADR-003 remains the owner of tenant-facing runtime
  behavior.
* Frontend technology choices must not redefine tenant resolution, tenant context lifecycle,
  central-vs-tenant data ownership, Back Office authorization, or tenant-local authorization.
* Frontend authentication and authorization behavior must be derived from backend-provided state,
  capabilities, and API responses. Client-side checks are not an authorization boundary.
* Tenant-context integration in frontend code must reflect resolved backend tenant context. Frontend
  state must not invent, silently switch, or persist tenant context in a way that bypasses ADR-001 or
  ADR-003.
* Shared API clients, route metadata, and navigation state must consume backend-provided route
  exposure, capabilities, authentication state, and tenant context instead of duplicating those
  decisions client-side.
* Shared frontend packages and UI components must be usable across Back Office and tenant-facing
  surfaces unless a component is intentionally boundary-specific.
* Shared frontend components and layouts must preserve consistent accessibility, responsive
  behavior, and interaction patterns across Back Office and tenant-facing surfaces.
* Vuetify is the default component and interaction foundation. Competing primary UI frameworks or
  design systems require a later ADR.
* Pinia is the default shared client-side state store. Component-local state and composables remain
  appropriate for local UI behavior.
* XState is reserved for workflows with explicit states, transitions, guards, retries,
  cancellation, branching outcomes, or long-running orchestration. It must not replace Pinia as the
  general application state store.
* Vite is the default frontend build foundation.
* This ADR does not define concrete Back Office screens, tenant workspace structure, tenant-facing
  URL topology, navigation maps, login flows, design tokens, component APIs, form conventions, API
  endpoint contracts, or implementation-level frontend coding standards.

## Validation

Important invariants should be enforced through dependency review, frontend tests where practical,
build configuration review, and code review of frontend package and application-surface changes.

Expected validation includes:

* Code review must reject introducing a competing primary frontend framework, UI framework, build
  system, or general-purpose state-management solution without a superseding ADR.
* Code review must reject frontend changes that treat client-side authorization checks as the source
  of truth for Back Office or tenant-facing access.
* Code review must reject frontend tenant-context handling that bypasses backend tenant resolution or
  stores tenant context in a way that can outlive or contradict the backend execution context.
* Code review must reject shared API clients, route metadata handling, or navigation state that
  duplicates backend-owned route exposure, authorization, authentication, or tenant-context
  decisions.
* Once frontend package manifests exist, validation should include dependency policy checks, type
  checking, production build verification, and focused frontend tests for shared foundation code.
* Frontend tests should cover shared state stores, workflow machines, API integration behavior, and
  reusable components when those units carry cross-surface behavior or security-sensitive state.
* Shared components and layouts should be reviewed for accessibility, responsive behavior, and
  interaction consistency before they become cross-surface frontend foundation code.
* Build and dependency review must keep shared frontend packages consumable by Back Office and
  tenant-facing surfaces unless a package is intentionally boundary-specific.

## Revisit When

Revisit this decision if the application adopts a different primary frontend framework, replaces
Vuetify as the shared UI foundation, introduces a separate frontend foundation for Back Office or
tenant-facing applications, or changes application delivery in a way that makes the current
frontend/backend integration model unsuitable.

## References

* [ADR-001: Tenancy Architecture](<ADR-001 - Tenancy Architecture.md>)
* [ADR-002: Back Office](<ADR-002 - Back Office.md>)
* [ADR-003: Tenant Application Boundary](<ADR-003 - Tenant Application Boundary.md>)
