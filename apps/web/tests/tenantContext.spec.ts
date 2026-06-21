import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

import { TenantContextUnavailableError, useTenantContextStore } from '@/shared/stores/tenantContext';
import type { BackendSession, ResolvedTenantContext } from '@/shared/types/backend';

function session(tenant: ResolvedTenantContext | null): BackendSession {
    return {
        auth: { authenticated: tenant !== null, surface: tenant ? 'tenant_facing' : null, identityRef: null },
        capabilities: [],
        exposedRoutes: [],
        tenant,
    };
}

const ACME: ResolvedTenantContext = { slug: 'acme', displayName: 'Acme' };
const GLOBEX: ResolvedTenantContext = { slug: 'globex', displayName: 'Globex' };

describe('tenant context store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('does not invent a tenant when the backend resolved none', () => {
        const store = useTenantContextStore();
        store.applyBackendResolved(session(null));

        expect(store.activeTenant).toBeNull();
        expect(store.tenantScopedReady).toBe(false);
        expect(store.slug).toBeNull();
    });

    it('mirrors exactly the backend-resolved tenant', () => {
        const store = useTenantContextStore();
        store.applyBackendResolved(session(ACME));

        expect(store.activeTenant).toEqual(ACME);
        expect(store.slug).toBe('acme');
        expect(store.tenantScopedReady).toBe(true);
    });

    it('exposes no setter to switch tenant from an arbitrary client input', () => {
        const store = useTenantContextStore();
        // The only way to change tenant is applyBackendResolved / reconcileWith,
        // both of which take backend-resolved data — there is no setSlug(...).
        expect((store as unknown as Record<string, unknown>).setSlug).toBeUndefined();
        expect((store as unknown as Record<string, unknown>).setTenant).toBeUndefined();
    });

    it('only updates the active tenant from a backend-resolved response', () => {
        const store = useTenantContextStore();
        store.applyBackendResolved(session(ACME));
        // Simulate the backend resolving a switch to globex.
        store.applyBackendResolved(session(GLOBEX));

        expect(store.slug).toBe('globex');
    });

    it('discards stale persisted context in favor of backend truth', () => {
        const store = useTenantContextStore();
        store.applyBackendResolved(session(ACME));

        const wasStale = store.reconcileWith(GLOBEX);

        expect(wasStale).toBe(true);
        expect(store.slug).toBe('globex');
    });

    it('does not persist tenant context to web storage', () => {
        const setItem = vi.spyOn(Storage.prototype, 'setItem');
        const store = useTenantContextStore();

        store.applyBackendResolved(session(ACME));
        store.reconcileWith(GLOBEX);
        store.failClosed();

        expect(setItem).not.toHaveBeenCalled();
        setItem.mockRestore();
    });

    it('fails closed (withholds tenant-scoped state) when context is unknown', () => {
        const store = useTenantContextStore();
        store.applyBackendResolved(session(ACME));

        store.failClosed();

        expect(store.tenantScopedReady).toBe(false);
        expect(store.activeTenant).toBeNull();
        expect(() => store.requireSlug()).toThrow(TenantContextUnavailableError);
    });
});
