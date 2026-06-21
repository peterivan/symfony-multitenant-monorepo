import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import type { AuthState, BackendSession, Capability, ExposedRoute } from '@/shared/types/backend';

const UNAUTHENTICATED: AuthState = { authenticated: false, surface: null, identityRef: null };

/**
 * Holds the backend-provided session snapshot: authentication state, capabilities,
 * and exposed routes. All values originate from the backend; nothing here is a
 * client-side access decision (ADR-004).
 */
export const useSessionStore = defineStore('session', () => {
    const auth = ref<AuthState>(UNAUTHENTICATED);
    const capabilities = ref<readonly Capability[]>([]);
    const exposedRoutes = ref<readonly ExposedRoute[]>([]);
    const loaded = ref(false);

    const isAuthenticated = computed(() => auth.value.authenticated);

    /** Replace local state from a backend session snapshot. */
    function applyBackendSession(session: BackendSession): void {
        auth.value = session.auth;
        capabilities.value = session.capabilities;
        exposedRoutes.value = session.exposedRoutes;
        loaded.value = true;
    }

    /** Fail closed: drop all session state to the unauthenticated baseline. */
    function clear(): void {
        auth.value = UNAUTHENTICATED;
        capabilities.value = [];
        exposedRoutes.value = [];
        loaded.value = true;
    }

    return { auth, capabilities, exposedRoutes, loaded, isAuthenticated, applyBackendSession, clear };
});
