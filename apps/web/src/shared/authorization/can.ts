import type { Capability } from '@/shared/types/backend';

/**
 * PRESENTATION-ONLY authorization check (ADR-004).
 *
 * `can` answers "should the UI show/enable this affordance?" — it is NEVER an
 * access-control decision. The backend is the authorization boundary; if the
 * client and backend disagree, the backend denial wins and the UI must degrade
 * safely (see {@link assertNonAuthoritative}). Do not gate data access, route
 * access, or mutations on this function.
 */
export function can(grantedCapabilities: readonly Capability[], required: Capability): boolean {
    return grantedCapabilities.includes(required);
}

/** True only if every required capability is present (presentation-only). */
export function canAll(grantedCapabilities: readonly Capability[], required: readonly Capability[]): boolean {
    return required.every((capability) => can(grantedCapabilities, capability));
}

/**
 * Documents intent at the call site: a client-side "allow" is advisory. Callers
 * that perform a backend action must still send it and honor the backend's
 * response regardless of this value.
 */
export const CLIENT_CHECK_IS_NON_AUTHORITATIVE = true as const;
