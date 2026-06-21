/**
 * Types mirroring the state the BACKEND owns and provides (ADR-001/003/004).
 *
 * These describe what the frontend *consumes*; they do not define backend
 * endpoint contracts (out of scope per ADR-004) and they must never be used to
 * reimplement tenant resolution, authorization, or route exposure client-side.
 * The backend remains the source of truth.
 */

/**
 * The runtime surface a route/request belongs to. Mirrors the backend
 * `App\Http\Boundary\RouteBoundary` vocabulary — it is read from the backend,
 * never decided here.
 */
export type Surface = 'tenant_facing' | 'central_platform' | 'back_office';

/** A capability granted by the backend. Opaque string keys, defined backend-side. */
export type Capability = string;

/** Authentication state as resolved by the backend. */
export interface AuthState {
    readonly authenticated: boolean;
    /** Which authentication boundary the session belongs to, if any. */
    readonly surface: Surface | null;
    /** Opaque identity reference for display/attribution only — never an access decision. */
    readonly identityRef: string | null;
}

/**
 * Tenant context AS RESOLVED BY THE BACKEND. The absence of a resolved tenant is
 * represented explicitly so the frontend can fail closed rather than invent one.
 */
export interface ResolvedTenantContext {
    readonly slug: string;
    readonly displayName: string;
}

/** A route the backend has chosen to expose to this session. */
export interface ExposedRoute {
    readonly name: string;
    readonly path: string;
    readonly surface: Surface;
    /** Capabilities the backend says are required; consumed for presentation only. */
    readonly requiredCapabilities: readonly Capability[];
}

/**
 * The complete backend-provided session snapshot. Everything the frontend knows
 * about auth, capabilities, exposed routes, and tenant context originates here.
 */
export interface BackendSession {
    readonly auth: AuthState;
    readonly capabilities: readonly Capability[];
    readonly exposedRoutes: readonly ExposedRoute[];
    /** Resolved tenant context, or null when the backend resolved none. */
    readonly tenant: ResolvedTenantContext | null;
}
