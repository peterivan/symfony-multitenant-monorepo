# Shared frontend foundation (ADR-004)

This package is the single shared foundation for **all** browser surfaces — Back
Office (ADR-002) and tenant-facing applications (ADR-003). It is consumed by the
surfaces under `src/surfaces/`.

## Stack defaults (do not replace without a superseding ADR)

| Concern        | Default                                                    |
| -------------- | ---------------------------------------------------------- |
| UI framework   | Vue 3                                                      |
| Language       | TypeScript                                                 |
| UI components  | Vuetify                                                    |
| Shared state   | Pinia                                                      |
| Workflows      | XState — **only** for explicit stateful workflows          |
| Build / dev    | Vite                                                       |

XState is not a general store. Use Pinia (or component-local state/composables)
for ordinary state; reach for XState only when a feature has explicit states,
transitions, guards, retries, cancellation, or long-running orchestration (see
`machines/tenantSwitch.ts`).

## Backend is the source of truth

`api/` + `stores/` consume backend-provided auth state, capabilities, exposed
routes, and tenant context (`types/backend.ts`). The frontend must **not**
reimplement route exposure, authorization, or tenant resolution. All hydration
flows through `bootstrap.ts`.

- **Authorization** (`authorization/can.ts`) is **presentation-only**. It may
  hide/disable affordances; it is never an access decision. The backend enforces
  access and its denial always wins.
- **Tenant context** (`stores/tenantContext.ts`) only mirrors the
  backend-resolved tenant. It never invents a tenant, never switches silently
  (switching goes through the backend via `machines/tenantSwitch.ts`), never
  persists context that could outlive/contradict the backend, and **fails closed**
  (withholds tenant-scoped data/actions) when context is unknown/ambiguous/stale.

## Cross-surface components

Components and layouts here are cross-surface by default and must preserve
consistent accessibility, responsive behavior, and interaction patterns. A
component meant for a single surface must live under that surface in
`src/surfaces/<surface>/`, not be silently restricted inside this shared package.
