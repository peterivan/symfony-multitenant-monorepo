## Context

ADR-004 establishes a single shared Vue 3 frontend foundation for browser-based application surfaces. Back Office (ADR-002) is implemented on this foundation, and tenant-facing applications (ADR-003) use the same foundation unless a project-specific ADR defines a different one. The foundation standardizes Vue 3, TypeScript, Vuetify, Pinia, XState, and Vite, and owns shared frontend packages, UI components, layouts, client-side stores, workflow machines, API clients, authentication/tenant-context integration, route-metadata consumption, build configuration, and frontend test utilities.

The foundation does NOT own tenancy architecture, tenant-context lifecycle, Back Office authorization, tenant-local authorization, route exposure, or data ownership. Those remain defined by ADR-001, ADR-002, and ADR-003 and live in the backend. The backend is the source of truth: ADR-001 owns tenant resolution and context lifecycle, ADR-003 owns tenant-facing runtime behavior, and authentication/authorization decisions and route exposure are backend-provided. Frontend code consumes these decisions; it must not reimplement, invent, persist, or contradict them.

There is no frontend today. The backend is Symfony 8.1 / PHP 8.5 in `apps/server`, with backend tooling run via `docker compose exec app <cmd>`. The frontend introduces a new Vite/npm build that is foreign to the existing Docker-based PHP toolchain, so this change must decide where the frontend lives. AGENTS.md forbids MOVING code between apps, but creating a new frontend app is in scope here.

This change specs the foundation, its stack defaults, its workspace home, and the rules that keep frontend tenant-context and authorization handling subordinate to the backend. It does not define concrete screens, navigation maps, URL topology, login flows, design tokens, component APIs, form conventions, or API endpoint contracts (out of scope per ADR-004).

## Goals / Non-Goals

**Goals:**
- Establish the shared Vue 3 + TypeScript + Vuetify + Pinia + XState + Vite foundation as the default stack for browser-based surfaces, with Vuetify as the default UI framework and Pinia as the default client-side store.
- Decide and spec the frontend workspace home (`apps/web`), scaffolded with Vite and npm, without moving any existing backend code.
- Make both Back Office and tenant-facing surfaces build on this foundation unless a later ADR says otherwise.
- Spec backend-as-source-of-truth integration: shared API clients, route metadata, and navigation state consume backend-provided route exposure, capabilities, authentication state, and tenant context.
- Spec that the frontend never invents, silently switches, or persists tenant context in a way that bypasses or can outlive/contradict backend tenant resolution, and fails closed when tenant context is unknown, ambiguous, or stale.
- Spec that client-side authorization and capability checks are non-authoritative (presentation only) and never an access boundary.
- Spec cross-surface consistency of accessibility, responsive behavior, and interaction patterns for shared components and layouts.

**Non-Goals:**
- Defining concrete Back Office screens, tenant workspace structure, tenant-facing URL topology, navigation maps, or login flows (owned by ADR-002/ADR-003 and later changes).
- Defining design tokens, component APIs, form conventions, or implementation-level frontend coding standards.
- Defining tenant resolution, tenant-context lifecycle, central-vs-tenant data ownership, Back Office authorization, or tenant-local authorization (owned by ADR-001/ADR-002/ADR-003 in the backend).
- Defining API endpoint contracts or the backend mechanism that produces route exposure, capabilities, auth state, and tenant context.
- Selecting a competing primary UI framework, general-purpose state store, or build system (would require a superseding ADR).

## Decisions

- The shared frontend stack defaults are Vue 3 (UI), TypeScript (application code), Vuetify (primary UI components), Pinia (shared client-side state), XState (explicit workflow/finite-state orchestration), and Vite (build/dev tooling). These are defaults the foundation enforces, not per-surface choices.
- The frontend foundation lives in a new dedicated workspace at `apps/web`, separate from the Symfony backend in `apps/server`, and is scaffolded with Vite and npm. This satisfies ADR-004's call for shared frontend packages without moving any backend code out of `apps/server` (consistent with AGENTS.md). The backend keeps its Docker (`docker compose exec app`) toolchain; the frontend uses its own npm/Vite toolchain.
- Both Back Office and tenant-facing surfaces are built on this foundation unless a later project-specific ADR defines a different one. Shared packages (components, layouts, stores, machines, API clients, tenant-context/route-metadata consumers) must remain consumable across both surfaces unless a component is intentionally boundary-specific.
- The backend is the source of truth. Shared API clients, route metadata, and navigation state consume backend-provided route exposure, capabilities, authentication state, and tenant context rather than duplicating those decisions client-side.
- Frontend tenant context reflects resolved backend tenant context only. The frontend must not invent a tenant, silently switch tenants client-side, or persist tenant context in a way that bypasses backend resolution or can outlive/contradict the backend execution context. When the resolved tenant context is unknown, ambiguous, or stale, the frontend fails closed (treats tenant context as absent and does not present or act on tenant-scoped state).
- Switching tenant context is initiated as a request to the backend; the frontend updates its local context only from the backend's resolved response, never by directly mutating persisted client state to a different tenant.
- Client-side authorization and capability checks are presentation-only and non-authoritative. They may hide or disable UI affordances for usability, but they are never treated as an access decision; the backend enforces authorization and the UI must degrade safely if the client and backend disagree.
- Vuetify is the default component/interaction foundation and Pinia the default shared store. XState is reserved for workflows with explicit states, transitions, guards, retries, cancellation, branching outcomes, or long-running orchestration, and must not replace Pinia as the general store. Introducing a competing primary UI framework, general-purpose state store, or build system requires a superseding ADR.
- Shared components and layouts preserve consistent accessibility, responsive behavior, and interaction patterns across Back Office and tenant-facing surfaces.

## Risks / Trade-offs

- Risk: the frontend duplicates backend route-exposure, authorization, or tenant-context decisions for convenience, drifting from the backend source of truth. Mitigation: spec that shared API clients/route metadata/navigation consume backend-provided state, and reject duplication in code review.
- Risk: client state persists or silently switches tenant context (e.g. cached store, localStorage) and outlives or contradicts backend resolution, risking cross-tenant exposure. Mitigation: spec no-invent/no-silent-switch/no-bypassing-persistence rules plus fail-closed-on-unknown behavior, with negative scenarios.
- Risk: client-side authorization checks are treated as the access boundary, masking missing backend enforcement. Mitigation: spec checks as non-authoritative presentation-only and require the backend to remain the enforcement point.
- Risk: a competing UI framework, store, or build system creeps in surface-by-surface, fragmenting the foundation ADR-004 set out to unify. Mitigation: spec the defaults and require a superseding ADR for competitors.
- Trade-off: introducing a separate `apps/web` workspace with its own npm/Vite toolchain adds a second toolchain alongside the Docker-based PHP backend, but ADR-004 accepts this to keep frontend packages shareable across surfaces and to avoid coupling tenancy architecture to a UI.
- Trade-off: reserving XState only for explicit stateful workflows means some orchestration must justify itself versus plain Pinia, but this prevents XState from becoming a parallel general state store.
