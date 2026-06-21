## Why

The platform has no frontend yet, and ADR-004 mandates a single shared Vue 3 frontend foundation so the Back Office and future tenant-facing surfaces share UI vocabulary, state-management patterns, workflow modeling, build tooling, and backend-integration conventions instead of fragmenting into competing frontend architectures.

Without an explicit foundation, each surface would risk choosing different UI frameworks, state stores, and build systems, and — more dangerously — would risk reimplementing authentication, authorization, route exposure, and tenant-context decisions on the client. ADR-001 and ADR-003 make the backend the source of truth for tenant resolution, context lifecycle, and authorization; the frontend must consume those decisions, never redefine them.

This change establishes the shared frontend foundation (stack defaults, build tooling, and the app home) and the rules that keep frontend tenant-context and authorization handling subordinate to the backend. It does not define concrete Back Office screens, tenant workspace structure, URL topology, navigation maps, login flows, design tokens, or component APIs (out of scope per ADR-004).

## What Changes

- Establish a shared frontend foundation standardizing Vue 3, TypeScript, Vuetify (default UI components), Pinia (default client-side state store), XState (reserved for explicit stateful workflows), and Vite (build/dev tooling) as the defaults for browser-based surfaces.
- Create a new dedicated frontend workspace (`apps/web`) as the home of the shared foundation, scaffolded with Vite and npm, without moving any existing backend code out of `apps/server` (consistent with ADR-004 and AGENTS.md).
- Require both the Back Office and tenant-facing surfaces to build on this foundation unless a later project-specific ADR defines a different one.
- Require shared API clients, route metadata, and navigation state to consume backend-provided route exposure, capabilities, authentication state, and tenant context instead of duplicating those decisions client-side.
- Forbid the frontend from inventing, silently switching, or persisting tenant context in any way that bypasses or can outlive/contradict backend tenant resolution (ADR-001/ADR-003); frontend tenant context must reflect resolved backend context and fail closed when it is unknown or stale.
- Define client-side authorization and tenant-capability checks as non-authoritative presentation-only behavior; the backend remains the authorization boundary, and the frontend must not treat client-side checks as access decisions.
- Require shared components and layouts to preserve consistent accessibility, responsive behavior, and interaction patterns across surfaces.
- Forbid introducing a competing primary UI framework, general-purpose state store, or build system without a superseding ADR.

## Capabilities

### New Capabilities

- `frontend-foundation`: Defines the shared Vue 3 + TypeScript + Vuetify + Pinia + XState + Vite foundation, its workspace home (`apps/web`), the stack defaults and their boundaries, backend-as-source-of-truth API/route/navigation integration, non-authoritative client-side authorization, and cross-surface accessibility/responsiveness/interaction consistency.
- `frontend-tenant-context-integration`: Defines how frontend code reflects resolved backend tenant context without inventing, silently switching, or persisting tenant context that bypasses ADR-001/ADR-003, including fail-closed behavior when tenant context is unknown, ambiguous, or stale.

### Modified Capabilities

## Impact

- Affected ADRs: ADR-004 (Frontend Architecture and UI Foundation) as the owning decision; ADR-001 (Tenancy Architecture) and ADR-003 (Tenant Application Boundary) for tenant-resolution and tenant-context rules the frontend must consume rather than redefine; ADR-002 (Back Office) as the first consumer surface.
- Affected systems: a new `apps/web` frontend workspace (Vite/npm) introduced alongside the existing `apps/server` Symfony backend; shared frontend packages (UI components, layouts, Pinia stores, XState machines, API clients, route-metadata and tenant-context consumers); frontend build and dependency tooling.
- The backend remains the source of truth: no backend tenant-resolution, authorization, route-exposure, or data-ownership behavior is changed by this frontend foundation.
- No application code, ADRs, or other change directories are modified by this planning artifact.
