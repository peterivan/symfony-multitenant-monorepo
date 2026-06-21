import type { SessionApi } from '@/shared/api/session';
import { useSessionStore } from '@/shared/stores/session';
import { useTenantContextStore } from '@/shared/stores/tenantContext';

/**
 * Loads the backend-owned session and reconciles frontend state with it.
 *
 * This is the single hydration path: both the session store and the tenant-context
 * store are driven from the backend snapshot. On any failure we fail closed —
 * unauthenticated session and absent tenant context — rather than trusting any
 * stale local state.
 */
export async function hydrateFromBackend(sessionApi: SessionApi): Promise<void> {
    const session = useSessionStore();
    const tenantContext = useTenantContextStore();

    try {
        const snapshot = await sessionApi.fetchSession();
        session.applyBackendSession(snapshot);
        tenantContext.applyBackendResolved(snapshot);
    } catch {
        session.clear();
        tenantContext.failClosed();
    }
}
