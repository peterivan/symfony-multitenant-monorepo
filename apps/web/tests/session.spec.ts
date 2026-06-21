import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

import { ApiClient } from '@/shared/api/client';
import { SessionApi } from '@/shared/api/session';
import { hydrateFromBackend } from '@/shared/bootstrap';
import { useSessionStore } from '@/shared/stores/session';
import { useTenantContextStore } from '@/shared/stores/tenantContext';
import type { BackendSession } from '@/shared/types/backend';

function jsonResponse(body: BackendSession): Response {
    return new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

const TENANT_SESSION: BackendSession = {
    auth: { authenticated: true, surface: 'tenant_facing', identityRef: 'tenant-user:1' },
    capabilities: ['tenant.documents.create'],
    exposedRoutes: [],
    tenant: { slug: 'acme', displayName: 'Acme' },
};

describe('backend session hydration', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('drives both stores from the backend snapshot', async () => {
        const fetch = vi.fn().mockResolvedValue(jsonResponse(TENANT_SESSION));
        const api = new SessionApi(new ApiClient({ fetch }));

        await hydrateFromBackend(api);

        expect(useSessionStore().isAuthenticated).toBe(true);
        expect(useSessionStore().capabilities).toContain('tenant.documents.create');
        expect(useTenantContextStore().slug).toBe('acme');
    });

    it('fails closed on a backend/network error', async () => {
        const fetch = vi.fn().mockRejectedValue(new Error('network down'));
        const api = new SessionApi(new ApiClient({ fetch }));

        await hydrateFromBackend(api);

        expect(useSessionStore().isAuthenticated).toBe(false);
        expect(useTenantContextStore().tenantScopedReady).toBe(false);
    });

    it('sends tenant switches to the backend rather than switching locally', async () => {
        const switched: BackendSession = { ...TENANT_SESSION, tenant: { slug: 'globex', displayName: 'Globex' } };
        const fetch = vi.fn().mockResolvedValue(jsonResponse(switched));
        const api = new SessionApi(new ApiClient({ fetch }));

        const result = await api.requestTenantSwitch('globex');

        expect(fetch).toHaveBeenCalledOnce();
        const [, init] = fetch.mock.calls[0]!;
        expect(init.method).toBe('POST');
        expect(JSON.parse(init.body as string)).toEqual({ slug: 'globex' });
        expect(result.tenant?.slug).toBe('globex');
    });
});
