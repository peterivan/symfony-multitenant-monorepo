## 1. Establish the frontend workspace

- [x] 1.1 Create the new frontend workspace directory `apps/web` alongside `apps/server`, without moving any backend code (consistent with ADR-004 and AGENTS.md)
- [x] 1.2 Scaffold a Vue 3 + TypeScript project with Vite and npm in `apps/web` (e.g. `npm create vite@latest apps/web -- --template vue-ts`)
- [x] 1.3 Verify the dev server and production build run: `npm install` then `npm run dev` and `npm run build` in `apps/web`
- [x] 1.4 Add `apps/web/node_modules` and Vite build output to `.gitignore`

## 2. Install and configure the default stack

- [x] 2.1 Install Vuetify as the default UI component framework and register it as the app's component foundation
- [x] 2.2 Install Pinia as the default shared client-side state store and register the Pinia plugin
- [x] 2.3 Install XState for explicit workflow orchestration (reserved for stateful workflows, not the general store)
- [x] 2.4 Confirm TypeScript type checking passes for the scaffolded app (e.g. `npm run type-check` / `vue-tsc --noEmit`)

## 3. Define the shared foundation structure

- [x] 3.1 Create directories for shared frontend packages: UI components, layouts, Pinia stores, XState machines, and API clients
- [x] 3.2 Ensure shared packages are consumable by both Back Office and tenant-facing surfaces unless a component is intentionally boundary-specific
- [x] 3.3 Establish accessibility, responsive-behavior, and interaction conventions for shared components and layouts

## 4. Wire backend-as-source-of-truth integration

- [x] 4.1 Implement a shared API client layer that consumes backend-provided authentication state, capabilities, route exposure, and tenant context
- [x] 4.2 Implement route-metadata and navigation state that derive exposed routes/surfaces from backend-provided route exposure and capabilities (no client-side duplication of backend decisions)
- [x] 4.3 Implement client-side authorization/capability checks as presentation-only (hide/disable affordances) and never as the access boundary

## 5. Implement tenant-context integration rules

- [x] 5.1 Implement frontend tenant context that mirrors the backend-resolved tenant context only (no client-side tenant resolution)
- [x] 5.2 Route tenant switching through the backend and update active tenant context only from the backend-resolved response (no silent client-side switch)
- [x] 5.3 Ensure tenant context is not persisted in a way that bypasses backend resolution or can outlive/contradict the backend execution context; discard stale persisted context
- [x] 5.4 Implement fail-closed handling when tenant context is unknown, ambiguous, or stale (withhold tenant-scoped data and actions)

## 6. Verify boundaries

- [x] 6.1 Add focused frontend tests for shared stores, API integration, and tenant-context handling, including negative cases (no invent / no silent switch / no bypassing persistence / fail closed)
- [x] 6.2 Confirm no competing primary UI framework, general-purpose state store, or build system is introduced without a superseding ADR
- [x] 6.3 Confirm the frontend does not redefine tenant resolution, tenant-context lifecycle, Back Office authorization, tenant-local authorization, route exposure, or data ownership (owned by ADR-001/ADR-002/ADR-003)
